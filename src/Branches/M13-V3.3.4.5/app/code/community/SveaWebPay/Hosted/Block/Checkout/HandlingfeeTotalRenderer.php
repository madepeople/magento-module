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
 
class SveaWebPay_Hosted_Block_Checkout_HandlingfeeTotalRenderer extends Mage_Checkout_Block_Total_Default
{ 
    protected $_template = 'sveawebpay/hosted/checkout/handlingfeetotalrenderer.phtml';
    
    private function getMethodInstance()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if(!$quote)
            return null;
        
        $payment = $quote->getPayment();
        if(!$payment)
            return null;
        
        if($payment->hasMethodInstance())
            return $payment->getMethodInstance();
            
        return null;
    }
    
    public function displayBoth()
    {
        $method = $this->getMethodInstance();
        if(!$method)
            return false;
        
        $type = $method->getConfigData('handling_fee_display_cart');
        return $type == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH;
    }
    
    public function displayIncl()
    {
        $method = $this->getMethodInstance();
        if(!$method)
            return true;
        
        $type = $method->getConfigData('handling_fee_display_cart');
        return $type == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX;
    }
}
?>