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
 
class SveaWebPay_Common_Helper_Data extends Mage_Core_Helper_Abstract
{
    
    public function isVersionAboveOnePointThree()
    {
        $versionStr = Mage::getVersion();
        $versionExploded = explode(".",$versionStr);
        if($versionExploded == null || count($versionExploded) < 1)
            return false;
        
        $versionImploded = implode(".",array($versionExploded[0],$versionExploded[1]));
        if($versionImploded == "1.4" || $versionImploded == "1.5" || $versionImploded == "1.6")
            return true;
        
        return false;
    }
    
    public function isMethodActive($methods,$quote)
    {
        if(!$quote)
        return false;
        
        foreach($quote->getPaymentsCollection() as $payment)
        {
            if (in_array($payment->getMethod(),$methods))
            {
                $methodInstance = $payment->getMethodInstance();
                if ($methodInstance)
                    if ($methodInstance->getConfigData("active") == 1)
                        return true;
            }
        }
        return false;
    }
    
    
    public function isHandlingfeeEnabled($methods,$quote)
    {
        if(!$quote)
        return false;
        
        foreach($quote->getPaymentsCollection() as $payment)
        {
            if (in_array($payment->getMethod(),$methods))
            {
                $methodInstance = $payment->getMethodInstance();
                if ($methodInstance)
                    if ($methodInstance->getConfigData("handling_fee") == 1)
                        return true;
            }
        }
        return false;
    }
    
    private function isImageLanguageSupported($language)
    {
        switch($language)
        {
            case "us":
            case "dk":
            case "fi":
            case "se":
            case "no":
                return true;
        }
        return false;
    }
    
    public function getSupportedImageUrl($type,$image)
    {
        $storeLanguageCode = Mage::app()->getLocale()->getLocaleCode();
        $countryCodes = explode('_', $storeLanguageCode);
        $country = strtolower($countryCodes[1]);
    
        $country = ($this->isImageLanguageSupported($country)) ? $country : "us";
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

        return $url."sveawebpay/".$country."/".strtolower($type)."/".$image;
    }
}