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
 
class SveaWebPay_Webservice_Helper_Data extends Mage_Core_Helper_Abstract
{
    
    public function getSubdirPath()
    {
        $array = explode( "/",$_SERVER["SCRIPT_NAME"] );
        if(array_pop($array) === null)
            return "";
        return implode( "/", $array );
    }
    
    /**
    * 
    * Get array with address information.
    * @param int $id Slot number of address that we want to use.(Usally zero).
    * @return Array False if failed othwerwise Address information contained in a array.
    */
    public function getAddressInformationById( $id )
    {
        $session = Mage::getSingleton( "sveawebpayws/session" );
        $array   = $session->getAddressArray();
        if( !is_array($array) || count($array) <= $id )
        {
            $log = Mage::helper("swpcommon/log");
            $log->log("Failed to get address information since id was out of range of address array.");
            return false;
        }
        return $array[ $id ];
    }
    
    /**
    * 
    * Retrive selected address row.
    * @return Array False if failed otherwise Array with address information.
    */
    protected function getSelectedAddressRow()
    {
        $session = Mage::getSingleton("sveawebpayws/session");
        $addressInformationArray    = $session->getAddressInformationArray();
        $selectedAddressInformation = $session->getSelectedAddressInformation();
        
        if( !is_integer($selectedAddressInformation) )
        $selectedAddressInformation = 0;
        
        $addressInformationRow = $addressInformationArray[ $selectedAddressInformation ];
        if( !is_array($addressInformationRow) )
        {
            $log = Mage::helper("swpcommon/log");
            $log->log("Could not find selected address information.");
            return false;
        }
        return $addressInformationRow; 
    }

}
?>