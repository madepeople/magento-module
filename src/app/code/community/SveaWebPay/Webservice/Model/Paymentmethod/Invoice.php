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
    
class SveaWebPay_Webservice_Model_Paymentmethod_Invoice extends SveaWebPay_Webservice_Model_Paymentmethod_Base
{
    
    protected $_code            = 'swpwsinvoice';
    protected $_transactionType = 'invoice';
    protected $_formBlockType   = 'sveawebpayws/paymentmethod_invoice';
    
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapturePartial       = true;
    protected $_canCapture              = true;
    protected $_canAuthorize            = false;
    
    public function __construct()
    {
        parent::__construct();
        $this->setPaymentModelName( "invoice" );
        
        // This is to prevent that we get an error if we try to do partinvoice.
        // We don't want to make the partial payment if we overriding the shipping tax.        
        $isOverridingShippingTax = $this->getConfigData("shipping_tax_override");
        $usePartial = (!$isOverridingShippingTax) ? true : false;
        $this->_canRefundInvoicePartial = $usePartial;
        $this->_canCapturePartial = $usePartial;
    }
        
    public function execute( $oOrder = null)
    { 
        $session         = Mage::getModel("sveawebpayws/session");
        $checkoutSession = Mage::getSingleton("checkout/session");
        
        $log = Mage::helper("swpcommon/log");
        $helperUrl = Mage::helper("swpcommon/url");
        
        $addresses = $session->getAddressArray();
        $said  = $session->getSelectedAddressId();
        $email = $session->getEmail();    
        
        // We don't need to check these session variables since we get them from address.
        // Wich must be executed before this method.
        $snr = $session->getSecurityNumber();
        $cc  = $session->getCountryCode();
        $ic  = $session->getIsCompany();
        
        $isFinnishDefaultCountry = $this->isDefaultCountryFinnish();
        if($said < 0)
            $said = 0;
        
        if( (empty($addresses) || $said >= count($addresses)) && !$isFinnishDefaultCountry )
        {
            $this->completeFailed($snr, $oOrder );
            return false;
        }
        
        if(!$isFinnishDefaultCountry)
            $address = $addresses[ $said ];
        
        $testmode        = $this->getConfigData("test");
        $url             = $helperUrl->getWebservice($testmode);
        $service         = new Service( $url );
        $createOrder     = new CreateOrder();
        $auth            = new ClientAuthInfo();
        $orderRequest    = new OrderRequest();
        $clientOrderInfo = new ClientOrderInfo();
        $invoiceRows     = Array();
        
        $auth->Username = $this->getConfigData("username");
        $auth->Password = $this->getConfigData("password");
        $auth->ClientNumber = $this->getConfigData("accnr");
        
        if(!$ic)
            $ic = false;
        
        if(!$isFinnishDefaultCountry)
            $addressSelector = $address["AddressSelector"];
        else
            $cc = "FI";
        
        $resultArray = $session->getOrderItemsArray();
        if( !$resultArray || empty($resultArray) )
        {
            $log->log("Preperation of invoice rows failed since we couldn't get calculated information of taxes and prices.");
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
        
        
        $clientOrderNr = $session->getReservedOrderId();
        
        if(!$isFinnishDefaultCountry)
            $clientOrderInfo->AddressSelector = $addressSelector;
        
        $clientOrderInfo->ClientOrderNr = $clientOrderNr;
        $clientOrderInfo->OrderDate = time();
        $clientOrderInfo->CountryCode = $cc;
        $clientOrderInfo->IsCompany = $ic;
        $clientOrderInfo->SecurityNumber = $snr;
        $clientOrderInfo->CustomerEmail = $email;
        $clientOrderInfo->PreApprovedCustomerId = 0;
        
        $orderRequest->Auth = $auth;
        $orderRequest->InvoiceRows = $invoiceRows;
        $orderRequest->Order = $clientOrderInfo;
        
        $log = Mage::helper("swpcommon/log");
        $createOrder->request = $orderRequest;
        $response = $service->CreateOrder( $createOrder );
        
        $helper = Mage::helper("sveawebpayws");
        
        // Actions to do if we failed with the request.
        if(!$response || !$response->CreateOrderResult || !$response->CreateOrderResult->Accepted)
        {
            $rejectionCode = $response->CreateOrderResult->RejectionCode;
            $log->log( "CreateOrder failed with rejection code: ".$rejectionCode );
            
            $rejectionCode = $helper->__( "CreateOrder".$rejectionCode );
            $this->errorMessage($rejectionCode);
            $this->completeFailed($snr,$oOrder);
            return false;
        }
        
        // Save new information to database wich we will use when we create invoice.
        // We creates invoice from observers check config.xml
        
        $this->updateShippingAndBilling($oOrder,$response->CreateOrderResult->ValidCustomer);
        
        $sveaOrderNr = $response->CreateOrderResult->SveaOrderNr;         
        $order = Mage::getModel("sveawebpayws/order");
        if(!$order->saveNewOrderInformation( $sveaOrderNr, $clientOrderNr ))
        {
            $log->log("CreateOrder failed to save response information in database.");
            $this->errorMessage();
            $this->completeFailed($snr,$oOrder);
            return false;
        }
        
        $this->completeReturn( $snr,$oOrder,$sveaOrderNr );
        
        // Reset sessions that we doesn't need.
        $session->unsReservedOrderId();
        $session->unsOrderItemsArray();
        $session->unsAddressArray();
        
        return true;
    }
    
}