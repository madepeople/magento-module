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
 
class SveaWebPay_Common_Helper_Language extends Mage_Core_Helper_Abstract
{
    private function convertLanguageCodeToStr( $lngCode ) 
    {
        $codeArray = explode("_",$lngCode);
        if(empty($codeArray))
            return "en";
            
        return strtolower($codeArray[0]);
    }
    
    public function convertMonthNumberToString( $number )
    {
        $languageCode = Mage::app()->getLocale()->getDefaultLocale();
        $language = $this->convertLanguageCodeToStr( $languageCode );
        
        switch($language)
        {
            case 'se':
                return $this->_convertMonthNumberToStringSE( $number );
                break;
            
            case 'en':
                return $this->_convertMonthNumberToStringSE( $number );
                break;
            
            default:
                return $number;
                break;
        }
    }
    
    private function _convertMonthNumberToStringSE( $number )
    {
        $a          = ":a";
        $e          = ":e";
        $result     = "";
        $lastNumber = ($number % 10);

        switch ($lastNumber)
        {
            case 1:
            case 2:
                if ($number == '11' || $number == '12')
                    return $number.$e;
                else
                    return $number.$a;
            break;
            default:
                return $number.$e;  
        }
    }
}