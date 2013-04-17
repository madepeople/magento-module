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
 
class SveaWebPay_Hosted_Block_Form extends Mage_Payment_Block_Form
{
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sveawebpay/hosted/form.phtml');
    }
    
    protected function displayImages($code)
    {
        if ($this->getMethod()->getConfigData('display_images') == 1)
        {
            $helper = Mage::helper("swpcommon");
            $url = $helper->getSupportedImageUrl("hosted","SveaWebPay_".$code.".png");
            echo "<img src=\"".$url."\"><br>";
        }
    }
    
    protected function getFee($tax=false)
    {
        $calculations = Mage::helper("swpcommon/calculations");
        $session = Mage::getSingleton("checkout/session");
        $quote = $session->getQuote();
        if(!$quote)
            return 0;
        
        $method = $this->getMethod();
        $handlingfees = $calculations->calculateHandlingfee($quote,$method);
        if(!$handlingfees || !is_array($handlingfees))
            return 0;
        
        $handlingfeeValueIncl = 0;
        foreach($handlingfees as $handlingfee)
        {
            $handlingfeeValueIncl += $handlingfee["value_incl"];
        }
        return $handlingfeeValueIncl;
    }
    
    protected function getFeeText()
    {
        $feeIncl = Mage::app()->getStore()->getCurrentCurrency()->format($this->getFee(true));
        $feeExcl = Mage::app()->getStore()->getCurrentCurrency()->format($this->getFee(false));
        echo $this->__('You will be charged an extra fee of %s', $feeExcl);
        if ($feeIncl!=$feeExcl)
            echo ' ('.$feeIncl . ' ' . $this->helper('tax')->getIncExcText(true).')';
    }
}
?>