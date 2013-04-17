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
 
class SveaWebPay_Webservice_Model_Invoice extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init("sveawebpayws/invoice");
    }
    
    /**
    * 
    * Load a invoice class by the invoice id (Invoice id stored in magento, not in sveawebpay_webservice_invoice).
    * @param int $invoiceId
    * @return Sveawebpay_Webservice_Model_Mysql4_Invoice
    */
    public function loadByInvoiceId( $invoiceId )
    {
        return $this->getCollection()->addFilter( "magento_invoice_id",$invoiceId )->load()->getFirstItem();
    }
    
    /**
    * 
    * Enter description here ...
    * @param $swpwsOrderId
    * @param $swpInvoiceNr
    * @param $invoiceId
    * @param $amount
    * @param $date
    * @param $dueDate
    * @param $pdfLink
    */
    public function saveInformation($swpwsOrderId = "",$swpInvoiceNr = "",$invoiceId = "",$swpOrderId = "",$amount = "",$date = "",$dueDate = "",$pdfLink = "")
    {
        $log = Mage::helper("swpcommon/log");
        try
        {
            $invoice = Mage::getModel("sveawebpayws/invoice");
            $invoice->setAmount( sprintf("%.2f",$amount) );
            $invoice->setNumber( $swpInvoiceNr );
            $invoice->setDate( $date );
            $invoice->setDueDate( $dueDate );
            $invoice->setPdflink( $pdfLink );
            $invoice->setSveawebpayWebserviceOrderId( $swpwsOrderId );
            $invoice->setMagentoInvoiceId( $invoiceId );
            $invoice->save();
        }
        catch( Exception $e )
        {
            $log->exception( "Exception in Sveawebpay Webservice helper class: ".$e->getMessage() );
            return false;
        }
        return true;
    }
}