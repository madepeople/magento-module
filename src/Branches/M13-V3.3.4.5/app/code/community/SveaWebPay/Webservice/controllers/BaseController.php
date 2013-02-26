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

class SveaWebPay_Webservice_BaseController extends Mage_Core_Controller_Front_Action
{ 
    protected $_sendNewOrderEmail = true;
    
    private function getInnerRequestValue($key)
    {
        $request  = Mage::app()->getRequest();
        return ($request->isPost()) ? $request->getPost($key) : $request->getQuery($key);
    }
    
    // We need to know sometimes if it's finnish or some other country, this is since we have som hacks running specific for finnish.
    private function isDefaultCountryFinnish( $method )
    {
        $defaultCountry = $method->getConfigData("default_country");
        if(!$defaultCountry)
            return false;
        
        $defaultCountry = Mage::getModel("sveawebpayws/source_defaultcountry")->convertCountryIdToCode( $defaultCountry );
        return ($defaultCountry == "FI");
    }
    
    /**
    * 
    * Get checkout session.
    * @return Mage_Checkout_Model_Session
    */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
    * 
    * Get base payment model.
    * @return Sveawebpay_Webservice_Paymentmethod_Base
    */
    public function getBase()
    {
        return $this->getPaymentModel();
    }
    
    /**
    * 
    * Get payment model
    * @param string $parent Name of the child model to use. (invoice,partpay)
    * @return Sveawebpay_Webservice_Model_Paymentmethod
    */
    public function getPaymentModel($parent = 'base')
    {
        return Mage::getSingleton('sveawebpayws/paymentmethod_'.$parent);
    }
    
    public function getAddressesAction()
    {
        $json = Mage::helper("sveawebpayws/json");
        $session = Mage::getSingleton("sveawebpayws/session");
        
        // Save all of this information in the session so that we can use it later.
        $snr = $this->getInnerRequestValue('swpwssnr');
        $ic  = $this->getInnerRequestValue('swpwsic');
        $c   = $this->getInnerRequestValue('swpwsc');
        
        if(!$snr || !$ic || !$c)
        {
            echo $json->encode();
            return;
        }
        
        $method = $this->getMethodByCode( $c );
        if(!$method)
        {
            echo $json->encode();
            return;
        }
        
        $defaultCountry = Mage::getModel("sveawebpayws/source_defaultcountry");
        $cc = $defaultCountry->convertCountryIdToCode( $method->getConfigData( "default_country" ) );
        
        $testmode = $method->getConfigData( "test" );
        $helperUrl = Mage::helper("swpcommon/url");
        $url = $helperUrl->getWebservice($testmode);
              
        $log = Mage::helper("swpcommon/log");
        $ic = ($ic == "false") ? false : true;
        $session->setSecurityNumber( $snr );
        $session->setCountryCode( $cc );
        $session->setIsCompany( $ic );
        
        $service = new Service( $url );
        $clientAuthInfo = new ClientAuthInfo();
        $clientAuthInfo->ClientNumber = $method->getConfigData("accnr");
        $clientAuthInfo->Username = $method->getConfigData("username");
        $clientAuthInfo->Password = $method->getConfigData("password");
        
        $customer = new GetCustomerAddressesRequest();
        $customer->SecurityNumber = $snr;
        $customer->CountryCode = $cc;
        $customer->IsCompany = $ic;
        $customer->Auth = $clientAuthInfo;
        
        $getAddresses = new GetAddresses();
        $getAddresses->request = $customer;
       
        $response = $service->GetAddresses( $getAddresses );
        if(!isset($response->GetAddressesResult) || !$response->GetAddressesResult->Accepted)
        {
            echo $json->encode();
            return;
        }
        
        if(!isset($response->GetAddressesResult->Addresses->CustomerAddress))
        {
            echo $json->encode();
            return;
        }
        
        $array = Array();
        $addresses = $response->GetAddressesResult->Addresses->CustomerAddress;
        foreach($addresses as $address)
        {
            $array[] = Array(
                    "FirstName" => (isset($address->FirstName)) ? $address->FirstName : "",
                    "LastName" => (isset($address->LastName)) ? $address->LastName : "",
                    "LegalName" => (isset($address->LegalName)) ? $address->LegalName : "",
                    "AddressLine2" => (isset($address->AddressLine2)) ? $address->AddressLine2 : "",
                    "AddressLine1" => (isset($address->AddressLine1)) ? $address->AddressLine1 : "",
                    "Postcode" => (isset($address->Postcode)) ? $address->Postcode : "",
                    "Postarea" => (isset($address->Postarea)) ? $address->Postarea : "",
                    "AddressSelector" => (isset($address->AddressSelector)) ? $address->AddressSelector : "",
                );
        }
        
        // We do not unset the array here since we will use it later.
        $session->setAddressArray( $array );
        $session->setSelectedAddressId( 0 ); // Select the first address first.
        
        $wait = "";
        if(count($array) > 1)
        $wait = "saf";
        
        $content = $this->displayAddressInformationBlock( $c );
        echo $json->encode($content,true,$wait);
    }
    
    public function showSelectedAddressAction()
    {
        $json = Mage::helper("sveawebpayws/json");
        $c = $this->getInnerRequestValue('swpwsc');
        $content = $this->displayAddressInformationBlock( $c );
        echo $json->encode( $content, true );
    }
    
    public function selectAddressIdAction()
    {
        $json = Mage::helper("sveawebpayws/json");
        $session = Mage::getSingleton("sveawebpayws/session");
        $said = $this->getInnerRequestValue('swpwssaid');
        $code = $this->getInnerRequestValue("swpwsc");
        $array = $session->getAddressArray();
        
        if($said < 0)
            $said = 0;
        
        if(empty($array) || $said >= count($array))
            echo $json->encode("",false); // Jsonecnode success false.
        
        $session->setSelectedAddressId( $said );
        $spp = $this->displaySelectedAddressInformationBlock( $code );
        echo $json->encode( $spp, true );
        
    }
    
    public function getSelectedAddressAction()
    {
        $json = Mage::helper("sveawebpayws/json");
        $session = Mage::getSingleton("sveawebpayws/session");
        $array = $session->getAddressArray();
        $said = $session->getSelectedAddressId();
        $result = true;
        
        if($said == -1)
            $said = 0;
        
        if(empty($array) || $said >= count($array) || $said < 0)
            $result = false;

        $array = $array[ $said ];
        echo $json->encode($array,$result);
    }
    
    public function getPaymentplanOptionsAction()
    {
        $log = Mage::helper("swpcommon/log");
        $json = Mage::helper("sveawebpayws/json");
        $session = Mage::getSingleton("sveawebpayws/session");
        $checkoutSession = Mage::getSingleton("checkout/session");
        $waitingStatus = ($session->getSelectedAddressId() == -1 && count($session->getAddressArray()) > 1) ? "wfsa" : "";
        
        // Get the quote we need it for preparing our order items.
        $quote = $checkoutSession->getQuote();
        
        $method = Mage::getModel("sveawebpayws/paymentmethod_partpay");
        if(!$method->prepareOrderItems($quote))
        {
            $log->log("Preperation of invoice rows failed since we couldn't get calculated information of taxes and prices.");
            return false;
        }
        
        $auth = new ClientAuthInfo();
        $auth->Username = $method->getConfigData("username");
        $auth->Password = $method->getConfigData("password");
        $auth->ClientNumber = $method->getConfigData("accnr");
        
        $invoiceRows = Array();
        $resultArray = $session->getOrderItemsArray();
        if( !$resultArray || empty($resultArray) )
        {
            $log->log("Preperation of invoice rows failed since we couldn't get calculated information of taxes and prices.");
            echo $json->encode();
            return false;
        }
        
        $calculations = Mage::helper("swpcommon/calculations");
        $handlingfees = $calculations->calculateHandlingfee( $method->getQuote(),$method );
        
        $moreThanOneHandlingfee = (count($handlingfees) > 1);
        $handlingfeeTitle = $method->getHandlingFeeTitle();
        
        foreach( $handlingfees as $handlingfee)
        {
            $resultArray[] = $calculations->buildHandlingFeeArray(
                    $handlingfee["value_excl"],
                    $handlingfeeTitle,
                    $handlingfee["rate"],
                    $moreThanOneHandlingfee
                );
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
        
        $invoiceRowInfo = new ClientAuthInfo();
        $paymentplanOptionsRequest = new GetPaymentPlanOptionsRequest();
        $paymentplanOptionsRequest->Amount = 0;
        $paymentplanOptionsRequest->InvoiceRows = $invoiceRows;
        $paymentplanOptionsRequest->Auth = $auth;
        
        $paymentplanOptions = new GetPaymentPlanOptions();
        $paymentplanOptions->request = $paymentplanOptionsRequest;
        
        $testmode = $method->getConfigData( "test" );
        $helperUrl = Mage::helper("swpcommon/url");
        $url = $helperUrl->getWebservice($testmode);      
        
        $service = new Service( $url );
        $response = $service->GetPaymentPlanOptions( $paymentplanOptions );
        
        if(!$response || !$response->GetPaymentPlanOptionsResult->Accepted)
        {
            echo $json->encode();
            return false;
        }
        
        if(isset($response->GetPaymentPlanOptionsResult->PaymentPlanOptions) && !empty($response->GetPaymentPlanOptionsResult->PaymentPlanOptions))
        {
            $paymentplansArray = Array();  
            $swpPPOArray = null;
            
            $swpPPOArray = (isset($response->GetPaymentPlanOptionsResult->PaymentPlanOptions->PaymentPlanOption)) 
                ? ($response->GetPaymentPlanOptionsResult->PaymentPlanOptions->PaymentPlanOption) : null;
            
            if($swpPPOArray !== null)
            {            
                foreach($swpPPOArray as $ppOption)
                {
                    $paymentplansArray[] = Array(
                            "CampainCode" => $ppOption->CampainCode,
                            "Description" => $ppOption->Description,
                            "PaymentPlanType" => $ppOption->PaymentPlanType,
                            "ContractLengthInMonths" => $ppOption->ContractLengthInMonths,
                            "MonthlyAnnuity" => $ppOption->MonthlyAnnuity,
                            "InitialFee" => $ppOption->InitialFee,
                            "NotificationFee" => $ppOption->NotificationFee,
                            "InterestRatePercent" => $ppOption->InterestRatePercent,
                            "EffectiveInterestRatePercent" => $ppOption->EffectiveInterestRatePercent,
                            "NrOfInterestFreeMonths" => $ppOption->NrOfInterestFreeMonths,
                            "NrOfPaymentFreeMonths" => $ppOption->NrOfPaymentFreeMonths
                        );
                }
            }
        }
        
        if(!empty($paymentplansArray))
        {
            $session->setPaymentplansArray( $paymentplansArray );
            $pp = $this->displayPaymentplanBlock();
            echo $json->encode( $pp, true, $waitingStatus );
            return true;
        }
        
        echo $json->encode( "",false );
        return false;
    }
    
    
    
    public function showSelectedPaymentplanAction()
    {
        $optionId = $this->getInnerRequestValue('optionId');
        $session = Mage::getSingleton("sveawebpayws/session");
        $array = $session->getPaymentplansArray();
        
        if($optionId < 0)
           $optionId = 0;
        
        if(empty($array) || $optionId >= count($array))
            return;
        
        $info = $array[ $optionId ];
        
        // Campian code and paymnetplannr.
        $session->setCampainCode($info["CampainCode"]);
        $session->setSelectedPaymentplanId( $optionId );
        $spp = $this->displaySelectedPaymentplanBlock();
        
        echo $spp;
    }
    
    private function getMethodByCode( $code )
    {    
        // Hardcoded values since we already know what to use.
        if($code == "swpwsinvoice")
            return Mage::getModel("sveawebpayws/paymentmethod_invoice");
        else if($code == "swpwspartpay")
            return Mage::getModel("sveawebpayws/paymentmethod_partpay");
        return null;
    }
    
    private function errorMessage( $errorMessage = "Payment process has been canceled." )
    {
        $session = Mage::getSingleton("checkout/session");
        $session->setErrorMessage( $this->__( $errorMessage ) );
    }
    
    /**
    * Show payment plan options
    */
    private function displayPaymentplanBlock()
    {
        $newBlock = Mage::getBlockSingleton( "sveawebpayws/paymentmethod_paymentplan" );
        return $newBlock->toHtml();
    }
    
    /**
    * Show payment plans
    */
    private function displaySelectedPaymentplanBlock()
    {
        $newBlock = Mage::getBlockSingleton( "sveawebpayws/paymentmethod_selectedpaymentplan" );
        return $newBlock->toHtml();
    }
    
    /**
    * Show addresses
    */
    private function displayAddressInformationBlock( $code )
    {
        $newBlock = Mage::getBlockSingleton( "sveawebpayws/paymentmethod_addressinformation" );
        $newBlock->setCode( $code );
        return $newBlock->toHtml();
    }
    
    /**
    * Show selected address.
    */
    private function displaySelectedAddressInformationBlock( $code )
    {
        $newBlock = Mage::getBlockSingleton( "sveawebpayws/paymentmethod_selectedaddressinformation" );
        $newBlock->setCode( $code );
        return $newBlock->toHtml();
    }
}