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

class SveaWebPay_Common_Helper_Log extends Mage_Core_Helper_Abstract
{
    
    private $_mRootPath = "/sveawebpay/";
    
    /**
    * 
    * Get correct filename for the module you uses and type of log.
    * @param string $module
    * @return string filename.
    */
    private function getDatedFilename($filename)
    {
        $date = date("y-m-d");
        return $this->_mRootPath.$date.$filename;
    }
    
    /**
    * 
    * Logs exceptions to exception file and throws a Mage exception aswell, if given statement.
    * This is to make sure that execution stops.
    * @param string $message
    * @param enumeration $module
    */
    public function throwExceptionIfNull($object,$message)
    {
        if(is_null($object))
        {
            $this->exception($message);
            Mage::throwException($message);
        }  
    }
    
    /**
    * 
    * Logs exceptions to exception file and throws a Mage exception aswell.
    * @param string $message
    * @param enumeration $module
    */
    public function throwException($message)
    {
        $this->exception($message);
        Mage::throwException($message);  
    }
        
    /**
    * 
    * Logs exceptions to exception file.
    * @param string $message
    * @param enumeration $module
    */
    public function exception( $message )
    {
        $this->mageLog($message," Exception.log");
    }
    
    /**
    * 
    * Log to file
    * @param string $message
    * @param enemuration $module
    */
    public function log( $message )
    {
        $this->mageLog($message," System.log");
    }
    
    private function mageLog( $message, $filename )
    {
        Mage::log($message,null,$this->getDatedFilename($filename));
    }
}
?>