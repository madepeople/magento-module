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

class SveaWebPay_Webservice_Model_Observer_Invoice
{
    public function save( $observer )
    {
        $log = Mage::helper("swpcommon/log");
        $helper = Mage::helper("swpcommon");
        if(!$helper->isVersionAboveOnePointThree())
        {
            $log->log("Observer -> Invoice : save, Invoice was null or was ment for refund.");
            return false;
        }
        
        $event   = $observer->getEvent();
        $invoice = $event->getInvoice();
        
        if(!$invoice || $invoice->getIsUsedForRefund())
        {
            $log->log("Observer -> Invoice : save, Invoice was null or was ment for refund.");
            return false;
        }
        
        $order = $invoice->getOrder();
        if(!$order)
        {
            $log->log("Observer -> Invoice : save, Could not get order.");
            return false;
        }
        
        $model = Mage::getModel("sveawebpayws/source_methods");
        $methods = $model->getPaymentMethods();
        
        if(!$helper->isMethodActive($methods,$order))
        {
            return false;
        }
        
        $calculations = Mage::helper("swpcommon/calculations");
        $isHandlingfeeInvoiced = $calculations->isHandlingfeeInvoiced( $order );
        $payment = $order->getPayment();
        if(!$payment)
        {
            $log->log("Observer -> Invoice : save, Could not get payment.");
            $this->throwException();
            return false;
        }
        
        // Depending on what method we use, we want to do different calls.
        $methodName = $payment->getMethod();
        if($methodName == "swpwsinvoice")
            $result = $this->createInvoice( $invoice,$order,$isHandlingfeeInvoiced );
        else if($methodName == "swpwspartpay")
            $result = $this->approvePaymentplan( $invoice,$order );
        
        if($result)
        {
            if(!$isHandlingfeeInvoiced)
            {
                $handlingfeestore = Mage::getModel("swpcommon/handlingfeestore");
                $collection = $handlingfeestore->getCollection()->addFilter("order_id",$order->getIncrementId());
                $collection->load();
                
                foreach($collection as $node)
                {
                    $node->setInvoiceId( $invoice->getIncrementId() );
                    $node->save();
                }
                $collection->save();
            }
            return true;
        }
        
        $this->throwException();
        return false;
    }
    
    public function createInvoice($invoice,$order,$isHandlingfeeInvoiced)
    {
        $orderResource = Mage::getModel("sveawebpayws/order");
        $swpOrder = $orderResource->loadByOrderId( $order->getIncrementId() );
        if(!$swpOrder)
        {
            $this->throwException();
            return false;
        }
        
        $swpOrderId = $swpOrder->getId();
        $swpSveaOrderId = $swpOrder->getSveaOrderId();
        
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
        
        $calculations = Mage::helper("swpcommon/calculations");
        $resultArray = Array();
        
        if(!$isHandlingfeeInvoiced)
        {    
            $handlingfees = $calculations->getHandlingfee($order);
            $moreThanOneHandlingfee = (count($handlingfees) > 1) ? true : false;
            
            foreach($handlingfees as $handlingfee)
            {
                $resultArray[] = $calculations->buildHandlingFeeArray(
                        $handlingfee["base_value"],
                        $method->getHandlingfeeTitle(),
                        $handlingfee["percent"],
                        $moreThanOneHandlingfee
                    );
            }
        }
        
        $log = Mage::helper("swpcommon/log");
        $calculations->loadCurrencyCodes( $order,$method );
        $invoiceItems = $calculations->getInvoiceItems( $invoice );
        $resultArray = $calculations->generateValues( $order, $method, $invoiceItems, !$isHandlingfeeInvoiced, 0, $resultArray );
        if(!$resultArray || empty($resultArray))
        {
            $this->throwException();
            return false;
        }
        
        $log = Mage::helper("swpcommon/log");
        $log->log("Logging this discount amount. yioiiihahoo...");
        $discountAmount = $order->getDiscountAmount();    
        // Adding discount amount.
        // Somehow I would think that we should check for discount > 0, however magento sees discount to be minus.
        // Thats why the ifstatement is written as is.
        if($discountAmount < 0)
            $resultArray[] = Array(
                  "name" => "Discount",
                  "price" => $discountAmount,
                  "tax" =>  0,
                  "qty" => 1);
        
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
            $clientInvoiceRowInfo->DiscountPercent = 0;
            
            $counter++;
            $invoiceRows[] = $clientInvoiceRowInfo;
        }
        
        $clientInvoiceInfo = new ClientInvoiceInfo();
        $clientInvoiceInfo->InvoiceRows = $invoiceRows;
        $clientInvoiceInfo->InvoiceDistributionForm = "Post";
        $clientInvoiceInfo->NumberOfCreditDays = 30;
        
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
        
        if(!$response || !$response->CreateInvoiceResult || !$response->CreateInvoiceResult->Accepted )
        {
            $helper = Mage::helper("sveawebpayws");
            $errorMessage = $response->CreateInvoiceResult->ErrorMessage;
            if($errorMessage)
                $this->throwException( $helper->__( "FailedToCreateInvoiceWithRejectionCode" ).": ".$helper->__( $errorMessage) );
            else
                $this->throwException( $helper->__( "FailedToCreateInvoiceWithRejectionCode" ).": ".$helper->__( "CreateInvoice".$response->CreateInvoiceResult->RejectionCode ) );
            return false;
        }
        
        $swpInvoiceNumber = $response->CreateInvoiceResult->InvoiceNumber;
        
        $newInvoice = Mage::getModel("sveawebpayws/invoice");
        if(!$newInvoice->saveInformation($swpOrderId,$swpInvoiceNumber,$invoice->getIncrementId(),
            $response->CreateInvoiceResult->InvoiceAmount,$response->CreateInvoiceResult->InvoiceDate,
            $response->CreateInvoiceResult->DueDate,$response->CreateInvoiceResult->PdfLink))
        {
            $this->throwException();
            return false;
        }
        
        return true;
    }

    public function approvePaymentplan($invoice,$order)
    {
        $helper = Mage::helper("sveawebpayws");
        
        $ppResource = Mage::getModel("sveawebpayws/paymentplan");
        $swpPaymentplan = $ppResource->loadByOrderId( $order->getIncrementId() );
        if(!$swpPaymentplan)
        {
            $this->throwException();
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
        
        $swpPPNr = $swpPaymentplan->getPaymentplanNumber();
        if($swpPPNr == null || $swpPPNr == "")
        {
            $this->throwException();
            return false;
        }
        
        $auth = new ClientAuthInfo();
        $auth->Username = $method->getConfigData("username");
        $auth->Password = $method->getConfigData("password");
        $auth->ClientNumber = $method->getConfigData("accnr");
        
        $approvePaymentplanRequest = new ApprovePaymentPlanRequest();
        $approvePaymentplanRequest->Auth = $auth;
        $approvePaymentplanRequest->SveaPaymentPlanNr = $swpPPNr;
        
        $approvePaymentplan = new ApprovePaymentPlan();
        $approvePaymentplan->request = $approvePaymentplanRequest;
        
        $testmode = $method->getConfigData( "test" );
        $helperUrl = Mage::helper("swpcommon/url");
        $url = $helperUrl->getWebservice($testmode);
        
        $service = new Service( $url );
        $response = $service->ApprovePaymentPlan( $approvePaymentplan );
        if(!$response || !$response->ApprovePaymentPlanResult->Accepted)
        {
            $rejectionCode = $response->ApprovePaymentPlanResult->RejectionCode;
            $errorMessage = $response->ApprovePaymentPlanResult->ErrorMessage;
            if($errorMessage)
                $this->throwException( $helper->__("FailedToApprovePaymentplanWithRejectionCode").":  ".$helper->__( $errorMessage ) );
            else
                $this->throwException( $helper->__("FailedToApprovePaymentplanWithRejectionCode").":  ".$helper->__("ApprovePaymentplan".$rejectionCode));
            return false;
        }
        
        $contractNumber = $response->ApprovePaymentPlanResult->ContractNumber;
        $swpPaymentplan->setContractNumber( $contractNumber );
        $swpPaymentplan->save();
        
        return true;
    }

    private function throwException( $str = null)
    {
        $helper = Mage::helper("sveawebpayws");
        $str = ($str == null) ? $helper->__( "FailedWithSaveOrCaptureOnline" ) : $str;
        Mage::throwException( $str );
    }

}