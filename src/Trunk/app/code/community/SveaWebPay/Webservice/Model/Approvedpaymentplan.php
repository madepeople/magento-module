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
 
class Sveawebpay_Webservice_Model_Approvedpaymentplan extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init("sveawebpayws/approvedpaymentplan");
    }
    
    public function saveNewApprovedPaymentplanInformation( $invoiceId,$contractNumber,$swpwsPaymentplanId )
    {
        $log = Mage::helper("swpcommon/log");
        try
        {
            $paymentplan = Mage::getModel("sveawebpayws/approvedpaymentplan"  );
            $paymentplan->setInvoiceId($invoiceId);
            $paymentplan->setContractNumber($contractNumber);
            $paymentplan->setSveawebpayWebservicePaymentplanId( $swpwsPaymentplanId );
            $paymentplan->save();
        }
        catch( Exception $e )
        {
            $log->exception( "Exception in Sveawebpay Webservice paymentplan class: ".$e->getMessage() );
            return false;
        }
        return true;
    }
}