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

class Sveawebpay_Hosted_Model_Sales_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    public function getTotalsForDisplay()
    {
        $helper = Mage::helper("swpcommon");
        $model   = Mage::getModel('sveawebpay/source_methods');
        $methods = $model->getPaymentMethods();
        $result = Array
            (
                'label' => "",
                'amount' => "",
                'font_size' => ""
            );
        
        $order = $this->getOrder();
        if(!$order)
            return  Array( $result );
        
        $invoice = $this->getSource();
        if(!$invoice)
            return Array( $result );
        
        if(!$helper->isMethodActive($methods,$order))
            return Array( $result );
        
        if(!$helper->isHandlingfeeEnabled($methods,$order))
            return Array( $result );
        
        $paymentMethod = $order->getPayment()->getMethodInstance();
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
        
        $amount = 0;
        foreach($collection as $handlingfee)
        {
            if ($handlingfee && $handlingfee->getHandlingfeeAmount() != 0)
            {          
                $rate = 1 + $handlingfee->getHandlingfeeTaxRate() / 100;
                $amount += $rate * $handlingfee->getHandlingfeeAmount();
            }
        }
        
        $amount = $this->getOrder()->formatPriceTxt($amount);
        if ($this->getAmountPrefix())
            $amount = $this->getAmountPrefix().$amount;
        
        
        $label    = $paymentMethod->getConfigData("handling_fee_description"). ':';
        $id       = $order->getIncrementId();
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        
        if(!isset($amount) || !isset($label) || !isset($fontSize))
            return Array( $result );
        
        $result["amount"]    = $amount;
        $result["label"]     = $label;
        $result["font_size"] = $fontSize;
        
        return Array( $result );
    }
}