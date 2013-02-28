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
 
class SveaWebPay_Hosted_Model_Sales_Order_View extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function __construct()
    {
        $this->setCode('handlingfee_hosted');
    }
    
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
        $helper = Mage::helper("swpcommon");
        $model = Mage::getModel('sveawebpay/source_methods');
        $methods = $model->getPaymentMethods();
        
        if(!$helper->isMethodActive($methods,$address->getQuote()))
            return $this;
        
        if(!$helper->isHandlingfeeEnabled($methods,$address->getQuote()))
            return $this;
        
        if(!$helper->isVersionAboveOnePointThree())
            return $this;
        
        return $this->applyHandlingfeeToGrandTotalAndTax( $address );
    }
    
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $helper  = Mage::helper("swpcommon");
        $model   = Mage::getModel('sveawebpay/source_methods');
        $methods = $model->getPaymentMethods();
        
        if(!$helper->isMethodActive($methods,$address->getQuote()))
            return Array();
        
        if(!$helper->isHandlingfeeEnabled($methods,$address->getQuote()))
            return Array();
        
        if(!$helper->isVersionAboveOnePointThree())
        {
            parent::collect( $address );
            $this->applyHandlingfeeToGrandTotalAndTax( $address );
        }
        
        parent::fetch($address);
        return $this->applyHandlingfeeToTotal( $address );
    }
    
    // add handling fee to grand total and tax
    private function applyHandlingfeeToGrandTotalAndTax( $address )
    {
        $helper       = Mage::helper("swpcommon");
        $result       = Array();
        
        if($helper->isVersionAboveOnePointThree())
        {
            if (!count($this->_getAddressItems($address)))
                return $result;
        }
        else
        {
            if (!count($address->getAllItems()))
                return $result;
        }
        
        $quote = $address->getQuote();
        if(!$quote)
            return $result;
        
		$payment = $quote->getPayment();
		if(!$quote)
			return $result;
			
        $paymentMethod = $payment->getMethodInstance();
        if(!$paymentMethod)
            return $result;
        
        $calculations = Mage::helper("swpcommon/calculations");
        if(!$calculations->loadCurrencyCodes( $quote, $paymentMethod ))
            return $result;
    
        $handlingfees = $calculations->calculateHandlingfee($quote,$paymentMethod);
        $applied      = $address->getAppliedTaxes();
        
        $handlingfeeValue        = 0;
        $handlingfeeBaseValue    = 0;
        $handlingfeeTaxValue     = 0;
        $handlingfeeBaseTaxValue = 0;
    
        foreach($handlingfees as $handlingfee)
        {
        
            $handlingfee["value_excl"] = $calculations->convertFromPaymentmethodToDisplayCurrency( $handlingfee["value_excl"] );
            $handlingfee["tax"]        = $calculations->convertFromPaymentmethodToDisplayCurrency( $handlingfee["tax"] );
            
            $handlingfeeValue        += $handlingfee["value_excl"];
            $handlingfeeTaxValue     += $handlingfee["tax"];
            $handlingfeeBaseValue    += $handlingfee["base_value_excl"];
            $handlingfeeBaseTaxValue += $handlingfee["base_tax"];
            
            $id = false;
            foreach($applied as $appliedRate)
            {
                if($appliedRate["percent"] == $handlingfee["rate"])
                {
                    $id = $appliedRate["id"];
                    break;
                }
            }
            if($id)
            {
                $applied[$id]['amount']      += $handlingfee["tax"];
                $applied[$id]['base_amount'] += $handlingfee["base_tax"];
            }
        }
        
        $address->setAppliedTaxes ( $applied );
        
        $handlingfeeValueIncl     = $handlingfeeValue     + $handlingfeeTaxValue;
        $handlingfeeBaseValueIncl = $handlingfeeBaseValue + $handlingfeeBaseTaxValue;
        
        if($helper->isVersionAboveOnePointThree())
        {
            $this->_addAmount( $handlingfeeValue + $handlingfeeTaxValue );
            $this->_addBaseAmount( $handlingfeeBaseValue + $handlingfeeBaseTaxValue );
            
            $address->setTaxAmount( $address->getTaxAmount() + $handlingfeeTaxValue );
            $address->setBaseTaxAmount( $address->getBaseTaxAmount() + $handlingfeeBaseTaxValue );
        }
        else
        {
            $address->setTaxAmount( $address->getTaxAmount() + $handlingfeeTaxValue );
            $address->setBaseTaxAmount( $address->getBaseTaxAmount() + $handlingfeeBaseTaxValue );
            
            $address->setGrandTotal( $address->getGrandTotal() + $handlingfeeValueIncl);
            $address->setBaseGrandTotal( $address->getBaseGrandTotal() + $handlingfeeBaseValueIncl );
            
            $quote->setGrandTotal( $quote->getGrandTotal() + $handlingfeeValueIncl );
            $quote->setBaseGrandTotal( $quote->getBaseGrandTotal() + $handlingfeeBaseValueIncl );
        }
        return $this;
    }
    
    // add handling fee to totals (this is what will be processed and sent to SWP later)
    private function applyHandlingfeeToTotal( $address )
    {
        $taxes = $this->getTax( $address );
        if(empty($taxes))
            return Array();
        
        $quote = $address->getQuote();
        if(!$quote)
            return Array();
			
		$payment = $quote->getPayment();
        if(!$payment || !$payment->hasMethodInstance())
            return Array();
			
        $paymentMethod = $payment->getMethodInstance();
        if(!$paymentMethod)
            return Array();
        
        $feeIncl     = $taxes["value"] + $taxes["tax"];
        $feeExcl     = $taxes["value"];
        $feeBaseExcl = $taxes["base_value"];
        
        $total = array(
                'code'            => $this->getCode(),
                'title'           => $paymentMethod->getHandlingFeeTitle(),
                'value_incl'      => $feeIncl,
                'value_excl'      => $feeExcl,
                'base_value_excl' => $feeBaseExcl,
                'value'           => 0
            );
        
        if($paymentMethod->getConfigData('handling_fee_display_cart') == Mage_Tax_Model_Config::DISPLAY_TYPE_EXCLUDING_TAX)
            $total['value'] = $total['value_excl'];
        else
            $total['value'] = $total['value_incl'];
        
        $address->addTotal($total);
    } 
    
    private function getTax( $address )
    {
        $result = Array();
        $helper = Mage::helper("swpcommon");
        if($helper->isVersionAboveOnePointThree())
        {
            if (!count($this->_getAddressItems($address)))
                return $result;
        }
        else
        {
            if (!count($address->getAllItems()))
                return $result;   
        }
        
        $quote = $address->getQuote();
        if(!$quote)
            return $result;
        
		$payment = $quote->getPayment();
		if(!$payment || !$payment->hasMethodInstance())
            return $result;
			
        $paymentMethod = $payment->getMethodInstance();
        if(!$paymentMethod)
            return $result;
        
        
        $calculations = Mage::helper("swpcommon/calculations");
        if(!$calculations->loadCurrencyCodes( $quote, $paymentMethod ))
            return $result;
        
        $handlingfees = $calculations->calculateHandlingfee( $quote,$paymentMethod );
        
        $handlingfeeValue        = 0;
        $handlingfeeBaseValue    = 0;
        $handlingfeeTaxValue     = 0;
        $handlingfeeBaseTaxValue = 0;
        
        foreach($handlingfees as $handlingfee)
        {
            $handlingfeeValue        += $handlingfee["value_excl"];
            $handlingfeeBaseValue    += $handlingfee["base_value_excl"];
            $handlingfeeTaxValue     += $handlingfee["tax"];
            $handlingfeeBaseTaxValue += $handlingfee["base_tax"];
        }
        
        $result = Array
            (
                "value"      => $handlingfeeValue,
                "base_value" => $handlingfeeBaseValue,
                "tax"        => $handlingfeeTaxValue,
                "base_tax"   => $handlingfeeBaseTaxValue,
            );
        return $result;
    }
}