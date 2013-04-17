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
 
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/Webservice/Service.php");

class SveaWebPay_Webservice_Model_Paymentmethod_Partpay extends SveaWebPay_Webservice_Model_Paymentmethod_Base
{
    protected $_code            = 'swpwspartpay';
    protected $_transactionType = 'partpay';
    protected $_formBlockType   = 'sveawebpayws/paymentmethod_partpay';
    
    protected $_canRefund               = false;
    protected $_canRefundInvoice        = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canCapturePartial       = false;
    protected $_canCapture              = true;
    protected $_canAuthorize            = true;

    public function __construct()
    {
        parent::__construct();
        $this->setPaymentModelName( "partpay" );     
    }

    public function execute( $oOrder = null )
    {
        $isFinnishDefaultCountry = $this->isDefaultCountryFinnish();
        $session = Mage::getModel("sveawebpayws/session");
        $addresses = $session->getAddressArray();
        $said = $session->getSelectedAddressId();
        
        $campainCode = $session->getCampainCode();
        $clientPaymentplanNr = $session->getReservedOrderId();
        $email = $session->getEmail();
        //$phone = $session->getPhone();
        
        $snr = $session->getSecurityNumber();
        $cc = $session->getCountryCode();
        $ic = $session->getIsCompany();
        
        if(!$ic)
            $ic = false;
        
        if(isset($said) || $said < 0)
            $said = 0;
        
        if((empty($addresses) || $said >= count($addresses)) && !$isFinnishDefaultCountry)
        {
            $this->errorMessage();
            $this->completeFailed($snr,$oOrder);
            return false;
        }
        
        
        // Hack for finnish.
        if(!$isFinnishDefaultCountry) {
            $address = $addresses[ $said ];
            $addressSelector = $address["AddressSelector"];
        }
        
        $auth = new ClientAuthInfo();
        $auth->Username = $this->getConfigData("username");
        $auth->Password = $this->getConfigData("password");
        $auth->ClientNumber = $this->getConfigData("accnr");
        
        $payplan = new ClientPaymentPlanInfo();
        
        // Hack for finnish.
        if(!$isFinnishDefaultCountry)
            $payplan->AddressSelector = $addressSelector;
        
        $payplan->CampainCode = $campainCode;
        $payplan->ClientPaymentPlanNr = $clientPaymentplanNr;
        $payplan->CountryCode = $cc;
        
        // Hack for finnish.
        if($isFinnishDefaultCountry)
            $payplan->CountryCode = "FI";
        
        $payplan->CustomerEmail = $email;
        //$payplan->CustomerPhoneNumber = $phone;
        
        // Set IsCompany to false here. This is done since no campany should be able to buy through this method.
        $payplan->IsCompany = false;
        $payplan->SecurityNumber = $snr;
        $payplan->SendAutomaticGiropaymentForm = false;
        
        
        $invoiceRows = Array();
        $resultArray = $session->getOrderItemsArray();
        $log = Mage::helper("swpcommon/log");
        if( !$resultArray || empty($resultArray) )
        {
            $log->log("CreatePaymentplan: preperation of invoice rows failed since we couldn't get calculated information of taxes and prices.");
            $this->errorMessage();
            $this->completeFailed($snr,$oOrder);
            return false;
        }
        
        $counter = 1;
        foreach( $resultArray as $invoiceRow )
        {
            $clientInvoiceRowInfo = new ClientInvoiceRowInfo();
            $clientInvoiceRowInfo->ClientOrderRowNr = $counter;
            $clientInvoiceRowInfo->Description = $invoiceRow["name"];
            $clientInvoiceRowInfo->PricePerUnit = $invoiceRow["price"];
            $clientInvoiceRowInfo->VatPercent = $invoiceRow["tax"];
            $clientInvoiceRowInfo->NrOfUnits = $invoiceRow["qty"];
            $clientInvoiceRowInfo->Unit = "unit";
            //$clientInvoiceRowInfo->ArticleNr = $counter;
            $clientInvoiceRowInfo->DiscountPercent = 0;
            
            $counter++;
            $invoiceRows[] = $clientInvoiceRowInfo;
        }
        
        $createPaymentplanRequest = new CreatePaymentPlanRequest();
        $createPaymentplanRequest->Auth = $auth;
        $createPaymentplanRequest->Amount = 0;
        $createPaymentplanRequest->PayPlan = $payplan;
        $createPaymentplanRequest->InvoiceRows = $invoiceRows;
        
        $createPaymentplan = new CreatePaymentPlan();
        $createPaymentplan->request = $createPaymentplanRequest;
        
        $testmode = $this->getConfigData( "test" );
        $helperUrl = Mage::helper("swpcommon/url");
        $url = $helperUrl->getWebservice($testmode);
        
        $helper = Mage::helper("sveawebpayws");
        $service = new Service( $url );
        $response = $service->CreatePaymentPlan( $createPaymentplan );
        
        //saveNewPaymentplanInformation
        if(!$response || !$response->CreatePaymentPlanResult || !$response->CreatePaymentPlanResult->Accepted)
        {
            $rejectionCode = $response->CreatePaymentPlanResult->RejectionCode;
            $errorMessage = $response->CreatePaymentPlanResult->ErrorMessage;
            $errorMesssage = ($errorMessage) ? $errorMessage : "";
            $log->log("CreatePaymentplan failed with rejectionCode: ".$rejectionCode. " ErrorMessage: ".$errorMessage);
            $rejectionCode = $helper->__( "CreatePaymentplan".$rejectionCode );
            $this->errorMessage($rejectionCode);
            $this->completeFailed($snr,$oOrder);
            return false;
        }
        
        $sppnr = $response->CreatePaymentPlanResult->SveaPaymentPlanNr;
        $amount = $response->CreatePaymentPlanResult->AuthorizedAmount;
        $orderId = $session->getReservedOrderId();
        
        $paymentplan = Mage::getModel("sveawebpayws/paymentplan");
        if(!$paymentplan->saveNewPaymentplanInformation( $amount,$sppnr,$orderId ))
        {
            $log->log("CreatePaymentplan failed, could not save information to database. OrderId: ".$oOrder->getId());
            $this->errorMessage();
            $this->completeFailed($snr,$oOrder);
            return false;
        }
        
        // Save new information to database wich we will use when we create invoice.
        // We creates invoice from observers check config.xml
        
        $this->updateShippingAndBilling($oOrder,$response->CreatePaymentPlanResult->ValidCustomer);
        
        // Reset sessions that we doesn't need.
        $session->unsReservedOrderId();
        $session->unsOrderItemsArray();
        $session->unsAddressArray();
        
        $session->unsCampainCode();
        $session->unsSelectedPaymentplanId();
        $session->unsEmail();
        //$session->unsPhone();
        
        $session->unsSecurityNumber();
        $session->unsCountryCode();
        $session->unsIsCompany();
        
        $this->completeReturn($snr, $oOrder,$sppnr);
    }
}