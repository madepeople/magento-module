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
 
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaOrder.php");

class SveaWebPay_HostedG_Model_Source_Exclude
{

    protected $_exludeCodes = Array
        (
            SveaOrder::CARD => "CARD",
            
            SveaOrder::DBSHBSE => "DBSHBSE",
            SveaOrder::DBSWEDBANKSE => "DBSWEDBANKSE",
            SveaOrder::DBSEBSE => "DBSEBSE",
            SveaOrder::DBDANSKEBANKSE => "DBDANSKEBANKSE",
            SveaOrder::DBNORDEASE => "DBNORDEASE",
             
            SveaOrder::SVEASPLITSE => "SVEASPLITSE",
            SveaOrder::SVEAINVOICESE => "SVEAINVOICESE",
        );

    public function toOptionArray($isMultiselect=false)
    {
        $options = $this->_exludeCodes;
        $options = Array();
        
        foreach($this->_exludeCodes as $key => $value)
            $options[] = Array('value'=> $key, 'label'=> $value);
        
        if(!$isMultiselect)
            array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
            
        return $options;
    }
}
