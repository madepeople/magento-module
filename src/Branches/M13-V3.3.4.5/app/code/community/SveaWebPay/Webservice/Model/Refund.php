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
 
class SveaWebPay_Webservice_Model_Refund extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init("sveawebpayws/refund");
    }
    
    /**
    * 
    * Save refund information.
    * @param int $invoiceId Id of Sveawebpay_Webservice_Invoice object.
    * @return bool True if success otherwise false.
    */
    public function saveInformation( $invoiceId, $amount, $number, $date, $dueDate, $pdfLink )
    {
        $log = Mage::helper("swpcommon/log");
        try
        {
            $refund = Mage::getModel("sveawebpayws/refund");
            $refund->setAmount( sprintf("%.2f",$amount)  );
            $refund->setNumber( $number  );
            $refund->setDate( $date  );
            $refund->setDueDate( $dueDate );
            $refund->setPdflink( $pdfLink );
            $refund->setSveawebpayWebserviceInvoiceId( $invoiceId );
            $refund->save();
        }
        catch( Exception $e )
        {
            $log->exception( "Exception in Sveawebpay Webservice helper class: ".$e->getMessage() );
            return false;
        }
        
        return true;
    }
}