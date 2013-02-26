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
 
class Sveawebpay_Webservice_Model_Sales_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    
    private function getFormattedPriceWithPrefix($amount)
    {
        $amount = $this->getOrder()->formatPriceTxt($amount);
        if ($this->getAmountPrefix()) {
            $amount = $this->getAmountPrefix().$amount;
        }
        return $amount;
    }
    
    private function calculateAmount($collection = null,$includinTax = true)
    {
        if($collection === null)
            return 0;
            
        $amount = 0;
        foreach($collection as $handlingfee) {
            if ($handlingfee && $handlingfee->getHandlingfeeAmount() != 0) {          
                $rate = ($includinTax) ? (1 + $handlingfee->getHandlingfeeTaxRate() / 100) : 1.0;
                $amount += $rate * $handlingfee->getHandlingfeeAmount();
            }
        }

        return $amount;
    }
    
    public function getTotalsForDisplay()
    {
        $helper = Mage::helper("swpcommon");
        $model   = Mage::getModel('sveawebpayws/source_methods');
        $methods = $model->getPaymentMethods();
        $result = Array
            (
                'label' => "",
                'amount' => "",
                'font_size' => ""
            );

            
        $order = $this->getOrder();
        if(!$order)
            return Array( $result );
        
        $invoice = $this->getSource();
        if(!$invoice)
            return Array( $result );
        
        if(!$helper->isMethodActive($methods,$order))
            return Array( $result );
        
        if(!$helper->isHandlingfeeEnabled($methods,$order))
            return Array( $result );
        
		$payment = $order->getPayment();
		if(!$payment || !$payment->hasMethodInstance())
			return Array( $result );
		
        $paymentMethod = $payment->getMethodInstance();
        if(!$paymentMethod)
            return Array( $result );
        
        $calculations = Mage::helper("swpcommon/calculations");
        $handlingFeeStore = Mage::getModel("swpcommon/handlingfeestore");
        if(!$order->getIncrementId())
            return Array( $result );
        
        $incrementId = $calculations->getHandlingfeeInvoiceId($order);
        if(!$incrementId || $incrementId != $invoice->getIncrementId())
            return Array( $result );
        
        $collection = $handlingFeeStore->getCollection()->addFilter("order_id",$order->getIncrementId())->load();
        if(!$collection || count($collection) <= 0)
            return Array( $result );
        
        $label    = $paymentMethod->getConfigData("handling_fee_description");
        $displayType = $paymentMethod->getConfigData('backend_handling_fee_display_order');
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
           
        if(!isset($fontSize))
            return Array( $result );
        
        if($displayType == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH)
        {
            $amount = $this->calculateAmount($collection,true);
            $amountIncl = $this->getFormattedPriceWithPrefix ($amount);
            
            $amount = $this->calculateAmount($collection,false);
            $amountExcl = $this->getFormattedPriceWithPrefix ($amount);
            
            $resultArray = Array();
            $resultArray[] = Array("amount" => $amountIncl,"label" => ($label . " (Incl):"),"font_size" => $fontSize);
            $resultArray[] = Array("amount" => $amountExcl,"label" => ($label . " (Excl):"),"font_size" => $fontSize);
            return $resultArray;
        }

        else if($displayType == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX)
        {
            $amount = $this->calculateAmount($collection,true);
            $amount = $this->getFormattedPriceWithPrefix ($amount);
            $result = Array("amount" => $amount, "label" => ($label . ":"), "font_size" => $fontSize);
            return Array($result);
        }
        
        // Excluding
        $amount = $this->calculateAmount($collection,false);
        $amount = $this->getFormattedPriceWithPrefix ($amount);
        $result = Array("amount" => $amount, "label" => ($label . ":"), "font_size" => $fontSize);
        

    }
}