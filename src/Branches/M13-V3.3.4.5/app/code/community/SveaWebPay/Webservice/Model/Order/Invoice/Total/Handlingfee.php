<?php

/**
 * SveaWebPay Payment Module for Magento.
 *   Copyright (C) 2012  SveaWebPay
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Any questions may be directed to kundtjanst.sveawebpay@sveaekonomi.se
 */
 
class SveaWebPay_Webservice_Model_Order_Invoice_Total_Handlingfee extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {        
        $helper       = Mage::helper("swpcommon");
        $calculations = Mage::helper("swpcommon/calculations");
        $model        = Mage::getModel('sveawebpayws/source_methods');
        $order        = $invoice->getOrder();
        $methods      = $model->getPaymentMethods();
        
        if(!$helper->isMethodActive($methods,$invoice->getOrder()))
            return $this;
        
        if(!$helper->isHandlingfeeEnabled($methods,$order))
            return $this;
        
        $handlingFeeStore = Mage::getModel("swpcommon/handlingfeestore");
        if($order->getIncrementId() == null)
            return $this;
        
        $collection = $handlingFeeStore->getCollection()->addFilter("order_id",$order->getIncrementId())->load(); 
        if(!$collection || count($collection) <= 0)
            return $this;
        
        $handlingfeeValue         = 0;
        $baseHandlingfeeValue     = 0;
        $taxAmount                = 0;
        $baseTaxAmount            = 0;
        $invoiceId                = "";
        
        foreach($collection as $handlingfee)
        {
            if ($handlingfee && $handlingfee->getHandlingfeeAmount() != 0)
            {
                if($handlingfee->getInvoiceId() && $invoiceId == "")
                    $invoiceId = $handlingfee->getInvoiceId();
                
                $rate = $handlingfee->getHandlingfeeTaxRate() / 100;
                $handlingfeeValue      += $handlingfee->getHandlingfeeAmount();
                $baseHandlingfeeValue  += $handlingfee->getHandlingfeeBaseAmount();
                $taxAmount             += $handlingfee->getHandlingfeeAmount()     * $rate;
                $baseTaxAmount         += $handlingfee->getHandlingfeeBaseAmount() * $rate;
            }
        }
        
        $handlingfeeValueIncl     = $handlingfeeValue     + $taxAmount;
        $baseHandlingfeeValueIncl = $baseHandlingfeeValue + $baseTaxAmount;
        
        $totalTax     = $invoice->getTaxAmount();
        $baseTotalTax = $invoice->getBaseTaxAmount();
        
        if(!$helper->isVersionAboveOnePointThree())
        {
            $invoice->setTaxAmount( $totalTax + $taxAmount );
            $invoice->setBaseTaxAmount( $baseTotalTax + $baseTaxAmount );      
            $invoice->setGrandTotal( $invoice->getGrandTotal() + $handlingfeeValueIncl );
            $invoice->setBaseGrandTotal( $invoice->getBaseGrandTotal() + $baseHandlingfeeValueIncl );
            return $this;
        }
        
        // If handlingfee has been used when creating another invoice we must not use it when creating this one.
        if($invoiceId != "")
            return $this;        
        
        if(!$invoice->isLast())
        {
            $totalTax     = $totalTax + $taxAmount;
            $baseTotalTax = $baseTotalTax + $baseTaxAmount;
            
            $invoice->setTaxAmount( $totalTax );
            $invoice->setBaseTaxAmount( $baseTotalTax );
            $invoice->setGrandTotal( $invoice->getGrandTotal() + $handlingfeeValueIncl );
            $invoice->setBaseGrandTotal( $invoice->getBaseGrandTotal() + $baseHandlingfeeValueIncl );
        }
        else
        {
            if(!$helper->isVersionAboveOnePointThree())
            {
                $handlingfeeValue += $taxAmount;
                $baseHandlingfeeValue += $baseTaxAmount;
            }
            $invoice->setGrandTotal( $invoice->getGrandTotal() + $handlingfeeValue );
            $invoice->setBaseGrandTotal( $invoice->getBaseGrandTotal() + $baseHandlingfeeValue );
        }
        return $this;
    }
}