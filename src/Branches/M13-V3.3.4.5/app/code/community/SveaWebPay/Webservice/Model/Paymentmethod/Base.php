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
 
class SveaWebPay_Webservice_Model_Paymentmethod_Base extends Mage_Payment_Model_Method_Abstract
{
    
    protected $_placeOrderConfirmUrl;
    
    protected $_isGateway               = true;
    protected $_canInvoice              = true;
    protected $_canAuthorize            = false;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canInvoicePartial       = false;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc               = false;
    
    protected $_paymentModelName        = "";
    protected $_requestCode;
    protected $_code = "";
    
    public function getCode()
    {
        return $this->_code;  
    }
    
    protected function updateShippingAndBilling($order,$validCustomer = null)
    {
        if($validCustomer == null)
            return false;
        
        $session         = Mage::getSingleton("sveawebpayws/session");
        $customerSession = Mage::getSingleton("customer/session");
        
        $firstname     = $validCustomer->LegalName;
        
        $addressline1  = $validCustomer->AddressLine1;
        $addressline2  = $validCustomer->AddressLine2;
        
        $postarea      = $validCustomer->Postarea;
        $postcode      = $validCustomer->Postcode;
        
        $billingPhone  = $session->getBillingPhone();
        $shippingPhone = $session->getShippingPhone();
        
        $country = $this->getConfigData("default_country");
        $model = Mage::getModel("sveawebpayws/source_defaultcountry");
        $country = $model->convertCountryIdToCode( $country );
        
        $address = null;
        if($this->getConfigData('update_billing'))
        {
            $address = $order->getBillingAddress();
            if($address)
            {
                $address->firstname = $firstname;
                $address->lastname  = "";
                $dilimiter = ", ";
                if($addressline1 == "" || $addressline2 == "")
                    $dilimiter = "";
                
                if($addressline1 != "" || $addressline2 != "")
                    $address->street = implode($dilimiter, array($addressline1,$addressline2));
                
                $address->region     = "";
                $address->telephone  = $billingPhone;
                $address->company    = "";
                $address->fax        = "";
                
                $address->city       = $postarea;
                $address->postcode   = $postcode;
                $address->country_id = $country;
                $address->save();
            }
            $order->setBillingAddress( $address );
        }
        if($this->getConfigData('update_shipping'))
        {
            $address = $order->getShippingAddress();
            if($address)
            {
                $address->firstname = $firstname;
                $address->lastname  = "";
                
                $dilimiter = ", ";
                if($addressline1 == "" || $addressline2 == "")
                $dilimiter = "";
                
                if($addressline1 != "" || $addressline2 != "")
                $address->street = implode($dilimiter, array($addressline1,$addressline2));
                
                $address->region     = "";
                $address->telephone  = $shippingPhone;
                $address->company    = "";
                $address->fax        = "";
                
                $address->city       = $postarea;
                $address->postcode   = $postcode;
                $address->country_id = $country;
                $address->save();
            }
            $order->setShippingAddress( $address );
        }
        return true;
    }
    
    
    
    protected function errorMessage( $errorMessage = "Payment process has been canceled." )
    {
        $helper = Mage::helper("sveawebpayws");
        $session = Mage::getSingleton("checkout/session");
        $session->setErrorMessage( $helper->__( $errorMessage ) );
    }
    
    // We need to now sometimes if it's finnish or some other country, this is since we have som hacks running specific for finnish.
    protected function isDefaultCountryFinnish()
    {
        $defaultCountry = $this->getConfigData("default_country");
        if(!$defaultCountry)
            return false;
        
        $defaultCountry = Mage::getModel("sveawebpayws/source_defaultcountry")->convertCountryIdToCode( $defaultCountry );
        return ($defaultCountry == "FI");
    }
    
    /**
    * 
    * Set the name of our payment model that is this class child.
    * @param string $name
    */
    protected function setPaymentModelName( $name )
    {
        $this->_paymentModelName = $name;
    }
    
    /**
    * 
    * Get name of payment model.
    * @return string Name of payment model.
    */
    public function getPaymentModelName()
    {
        return $this->_paymentModelName;
    }
    
    /**
    * 
    * Get checkouts session class.
    * @return Mage_Core_Model_Session_Abstract Session file.
    */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
    * 
    * Get quote object from checkouts session.
    * @return Mage_Sales_Model_Quote
    */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    
    public function getHandlingFeeEnabled()
    {
        return $this->getConfigData('handling_fee');
    }
    
    public function getHandlingFeeTitle()
    {
        return $this->getConfigData('handling_fee_description');
    }
    
    /**
    * 
    * Parent method used for redirection when we place our order.
    * @return string Url to our request controller to call upon soap requests.
    */
    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton("sveawebpayws/session");
        $orderRedirectUrl = $session->getOrderPlaceRedirectUrl();
        if($orderRedirectUrl == "")
            return Mage::getUrl("checkout/onepage/failure");
        return $orderRedirectUrl;
    }
    
    // Execution of create order or create paymentplan
    public function execute($order = null) { }
    
    /**
    * 
    * Inform user that we've failed with payment. And flag Magento's order object that the payment was not completed.
    * @param string $securityNr
    */
    public function completeFailed( $securityNr,$order )
    {
        $session = Mage::getSingleton("sveawebpayws/session");
        $session->setOrderPlaceRedirectUrl( Mage::getUrl( "checkout/onepage/failure", array('_secure'=>true) ) );
        
        if(!$order)
            return false;
        
        if ($order->getId()) {
            $helper = Mage::helper('sveawebpayws');
            $paymentText = $helper->__("CommonFailed").", ";
            $secnumber = $securityNr;
            
            if ($secnumber != "")
                $paymentText .= $helper->__("CommonSecurityNr - %s",$secnumber).", ";
                $paymentMethod = $this->_code;
                
            if ($paymentMethod != "")
                $paymentText .= $helper->__("CommonPaymentMethod - %s",$this->_code).", ";
            else
                $paymentText = substr($paymentText,0,-2);
                
            $order->registerCancellation($paymentText);
        }
        return true;
    }
    
    /**
    * 
    * Inform user that we've succeded with payment. And flag Magento's order object that payment was complete.
    * @param string $securityNr
    */
    public function completeReturn( $securityNr,$order,$sveaOrderId = null )
    {    
        $session = Mage::getSingleton("sveawebpayws/session");
        $session->setOrderPlaceRedirectUrl( Mage::getUrl( "checkout/onepage/success", array('_secure'=>true) ) );

        if(!$order)
            return false;
        
        $log = Mage::helper("swpcommon/log");
        $helper  = Mage::helper('sveawebpayws');
        $session = Mage::getSingleton("checkout/session");
        $session->addSuccess($helper->__("CreateSuccess"));
        
        if ($order && $order->getId())
        {
            // Check to see if customer has been notified, if so we skip this step.
            if($order->getEmailSent() == false)
            {   
                $paymentText = $helper->__("CommonConfirmed").", ";
                $secnumber = $securityNr;
                if ($securityNr != "")
                    $paymentText .= $helper->__("CommonSecurityNr - %s",$secnumber).", ";
                
                $paymentMethod = $this->_code;
                $sveaTransactionId = ($sveaOrderId !== null) ? " SveaWebPay Transaction ID: " . $sveaOrderId : "";
                $paymentText .= $helper->__("CommonPaymentMethod - %s",$paymentMethod).", " . $sveaTransactionId;
                
                // Send email and change sent flag.
                $order->sendNewOrderEmail();
                
                // Only change order status if we have changed order status.
                $newOrderStatus = $this->getConfigData('order_success_status');
                $order->setState($order->getState(), $newOrderStatus,$paymentText,true);
                
                if ($this->getConfigData('set_paid'))
                {
                    $order->setTotalPaid($order->getTotalPaid()+$order->getGrandTotal());
                    $order->setBaseTotalPaid($order->getBaseTotalPaid()+$order->getBaseGrandTotal());
                }
                $order->save();
            }

            // Solution found at nvncbl.com
            $cart  = Mage::getSingleton('checkout/cart');
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if($quote != null)
            foreach( $quote->getItemsCollection() as $item )
                $cart->removeItem( $item->getId() ); 

            $cart->save();
        }
        return true;
    }
    
    public function retrieveOrderInformation($order)
    {
        // Post Variables
        $securityNumber = Mage::app()->getRequest()->getParam($this->_code.":security_number");
        $email          = Mage::app()->getRequest()->getParam($this->_code.":email");
        $isComp         = Mage::app()->getRequest()->getParam($this->_code.":iscmp");
        
        // We have this many variables for phone, since if one desides to not use our 
        // phone field inside our form, the phone information should be used from the magento stores phone fields.
        $phone           = Mage::app()->getRequest()->getParam($this->_code.":phone");
        
        $shippingAddress = $order->getShippingAddress();
        $billingAddress  = $order->getBillingAddress();
        
        $shippingPhone   = ($phone) ? $phone : $shippingAddress->telephone;
        $billingPhone    = ($phone) ? $phone : $billingAddress->telephone;
        
        if($isComp == "1")
            $isComp = true;
        else $isComp = false;
        
        $session           = Mage::getSingleton("sveawebpayws/session");
        $session->setShippingPhone( $shippingPhone );
        $session->setBillingPhone( $billingPhone );
        $session->setSecurityNumber( $securityNumber );
        $session->setIsCompany( $isComp );
        $session->setEmail( $email );
        
    }
    
    /**
    * 
    * Calls calculations and builds up invoice rows information to be stored in session.
    * Information is then used inside both create order and create paymentplans.
    * @return bool False if anything failed true if we succeded in preparing our invoice rows.
    */
    public function prepareOrderItems($order)
    {
        $log               = Mage::helper("swpcommon/log");
        $calculationHelper = Mage::helper("swpcommon/calculations");
        $helper            = Mage::helper("sveawebpayws");
        $session           = Mage::getSingleton("sveawebpayws/session");
        
        $session->resetError();
        $incrementId = $order->getIncrementId();
        
        $calculationHelper->setCurrentOrderIdToProcessing( $incrementId );
        if(!$calculationHelper->loadCurrencyCodes( $order, $this ))
            return false;
        
        $objectItems = $calculationHelper->getQuoteItems( $order );
        $resultArray = $calculationHelper->generateValues( $order, $this, $objectItems );

        if( $resultArray == false )
        {
            $log->log("Preperation of invoice rows failed since we couldn't get calculated information of taxes and prices.");
            return false;
        }
        
        // Will be used in our other methods as an example within createOrder method inside Invoice class.
        $session->setOrderItemsArray( $resultArray );
        $session->setReservedOrderId( $incrementId );

        return true;
    }
}