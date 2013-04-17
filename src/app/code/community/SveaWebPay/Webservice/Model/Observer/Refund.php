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

class SveaWebPay_Webservice_Model_Observer_Refund
{
    public function save( $observer )
    {
        $helper = Mage::helper("swpcommon");
        if(!$helper->isVersionAboveOnePointThree())
            return false;
        
        $event = $observer->getEvent();
        $creditmemo = $event->getCreditmemo();
        $invoice = $creditmemo->getInvoice();
        
        if(!$invoice)
            return false;
        
        $order = $creditmemo->getOrder();
        if(!$order)
            return false;
        
        $payment = $order->getPayment();
        if(!$payment)
            return false;
        
        $method = $payment->getMethodInstance();
        if(!$method || $method->getCode() == "swpwspartpay")
            return false;
        
        $model        = Mage::getModel("sveawebpayws/source_methods");
        $methods      = $model->getPaymentMethods();
        
        if(!$helper->isMethodActive($methods,$order))
            return false;
        
        $payment = $order->getPayment();
        if(!$payment)
        {
            $this->throwException();
            return false;
        }
        
        // Refund
        return $this->refund($invoice,$order,$creditmemo);
    }
    
    
    
    public function refund($invoice,$order,$creditmemo)
    {
        $orderResource = Mage::getModel("sveawebpayws/order");
        $swpOrder = $orderResource->loadByOrderId( $order->getIncrementId() );
        if(!$swpOrder)
        {
            $this->throwException();
            return false;
        }
        
        $swpSveaOrderId = $swpOrder->getSveaOrderId();
        
        $helper  = Mage::helper("swpcommon");
        $model   = Mage::getModel("sveawebpayws/source_methods");
        $methods = $model->getPaymentMethods();
        
        if(!$helper->isMethodActive($methods,$order))
        {
            return false;
        }
        
        $payment = $order->getPayment();
        if(!$payment)
        {
            $this->throwException();
            return false;
        }
        
        $method = $payment->getMethodInstance();
        if(!$method)
        {
            $this->throwException();
            return false;
        }
        
        $swpInvoiceResource = Mage::getModel("sveawebpayws/invoice");
        $swpInvoice = $swpInvoiceResource->loadByInvoiceId( $invoice->getIncrementId() );
        if(!$swpInvoice)
        {
            $this->throwException();
            return false;
        }
        
        $swpInvoiceNumber = $swpInvoice->getNumber();
        $swpInvoiceId = $swpInvoice->getId();
        
        $calculations = Mage::helper("swpcommon/calculations");
        $shippingInformation = $calculations->retrieveShippingInformation($creditmemo);
        $value = $shippingInformation["price"];
        $qty = $shippingInformation["qty"];
        $tax = $shippingInformation["tax"];
        
        $refuntTotal = ($value * $qty * (1 + ($tax / 100)));
        
        if(!$calculations->loadCurrencyCodes( $invoice, $method ))
        {
            $this->throwException();
            return false;
        }
        
        $refuntTotal = $calculations->convertFromBaseToPaymentmethodCurrency($refuntTotal);
        $total = (($refuntTotal + $creditmemo->getBaseAdjustmentPositive()) - $creditmemo->getBaseAdjustmentNegative());
    
        $objectItems = $calculations->getCreditmemoItems( $creditmemo );
        $resultArray = $calculations->generateValues( $order, $method, $objectItems, false, $total );
        if(!$resultArray || empty($resultArray))
        {
            $this->throwException();
            return false;
        }

        $discountAmount = $order->getDiscountAmount();
            
        // Adding discount amount.
        // Somehow I would think that we should check for discount > 0, however magento sees discount to be minus.
        // Thats why the ifstatement is written as is.
        if($discountAmount < 0)
            $resultArray = $calculations->getDiscountOfOrder( $order,$objectItems,"Discount",$discountAmount,$resultArray );
        
        
        $auth = new ClientAuthInfo();
        $auth->Username = $method->getConfigData("username");
        $auth->Password = $method->getConfigData("password");
        $auth->ClientNumber = $method->getConfigData("accnr");
        
        $invoiceRows = Array();
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
        
        $clientInvoiceInfo = new ClientInvoiceInfo();
        $clientInvoiceInfo->InvoiceRows = $invoiceRows;
        $clientInvoiceInfo->InvoiceDistributionForm = "Post";
        $clientInvoiceInfo->NumberOfCreditDays = 30;
        $clientInvoiceInfo->InvoiceNrToCredit = $swpInvoiceNumber;
        
        $createInvoiceRequest = new CreateInvoiceRequest();
        $createInvoiceRequest->Auth = $auth;
        $createInvoiceRequest->Invoice = $clientInvoiceInfo;
        $createInvoiceRequest->SveaOrderNr = $swpSveaOrderId;
        
        $createInvoice = new CreateInvoice();
        $createInvoice->request = $createInvoiceRequest;
        
        $helperUrl = Mage::helper("swpcommon/url");
        $testmode = $method->getConfigData( "test" );
        $url = $helperUrl->getWebservice( $testmode );
        
        $service = new Service( $url );
        $response = $service->CreateInvoice( $createInvoice );
        if(!$response ||!$response->CreateInvoiceResult || !$response->CreateInvoiceResult->Accepted)
        {
            $this->throwException( $helper->__( "CreateInvoice" . $response->CreateInvoiceResult->RejectionCode ) );
            return false;
        }
        
        $refund = Mage::getModel("sveawebpayws/refund");
        if(!$refund->saveInformation($swpInvoiceId,$response->CreateInvoiceResult->InvoiceAmount,
            $response->CreateInvoiceResult->InvoiceNumber,$response->CreateInvoiceResult->InvoiceDate,
            $response->CreateInvoiceResult->DueDate,$response->CreateInvoiceResult->PdfLink))
        {
            $this->throwException();
            return false;
        }
    
        return true;
    }
    
    private function throwException( $str = null )
    {
        $helper = Mage::helper("sveawebpayws");
        $str = ($str == null) ? $helper->__("CouldNotCreateOrSaveCreditMemo") : $str;
        Mage::throwException( $str );
    }
}