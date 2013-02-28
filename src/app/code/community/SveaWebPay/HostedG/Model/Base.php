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
 
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaConfig.php");

class SveaWebPay_HostedG_Model_Base extends Mage_Payment_Model_Method_Abstract
{
    protected $_isGateway               = true;
    protected $_canInvoice              = true;
    protected $_canAuthorize            = false;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = true;
    protected $_canInvoicePartial       = false;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc               = false;
    protected $_paymentMethodName       = "";
    
    protected $_formBlockType = 'swphostedg/form';
    
    public function __construct()
    {
    }
    
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    
    public function getStore()
    {
        return $this->getQuote()->getStore();
    }
    
    public function getShippingAddress()
    {
        return $this->getQuote()->getShippingAddress();
    }
    
    public function getHandlingFeeEnabled()
    {
        return $this->getConfigData('handling_fee');
    }
    
    public function getHandlingFeeTitle()
    {
        return $this->getConfigData('handling_fee_description');
    }
    
    public function getOrderPlaceRedirectUrl()
    {
        return $this->getStandardCheckoutURL();
    }
    
    public function errorMessage( $errorMessage = "Payment process has been canceled." )
    {
        $helper  = Mage::helper("swphostedg");
        $session = Mage::getSingleton("checkout/session");
        $session->setErrorMessage( $helper->__( $errorMessage ) );
    }
    
    public function updateShippingAndBilling($response = null)
    {
        if($response == null)
            return false;
        
        $log = Mage::helper("swpcommon/log");
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order')->load($session->getLastOrderId());
        
        if (!$order->getId())
        {
            $log->log("Updating shipping and billing information failed since order was not found.");
            return false;
        }
        
        $session         = Mage::getSingleton("swphostedg/session");
        $customerSession = Mage::getSingleton("customer/session");
        
        $firstname     = $response->legalName;
        
        $addressline1  = $response->addressLine1;
        $addressline2  = $response->addressLine2;
        
        $postarea      = $response->postArea;
        $postcode      = $response->postCode;
        
        $billingPhone  = $session->getBillingPhone();
        $shippingPhone = $session->getShippingPhone();
        $country = "SE";
        
        $address = null;
        //if($this->getConfigData('update_billing')) {
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
        //}
        //if($this->getConfigData('update_shipping')) {
        
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
        //}
        return true;
    }
    
	private function getOrder()
    {
		$order = null;
        
        $session = Mage::getSingleton("checkout/session");
		$increment_id = $session->getData('last_real_order_id');

		if($increment_id)
		{
			$order = Mage::getModel('sales/order')->loadByIncrementId($increment_id);
			if(is_null($order->getId()))
			{
				$order = null;
			}
		}
        return $order;
    }
    
    public function prepare()
    {
        
        $sessionCheckout = Mage::getSingleton("checkout/session");
		$increment_id = $sessionCheckout->getData('last_real_order_id');

        $calculations = Mage::helper("swpcommon/calculations");
        
        // Get the orde instead of quote, this is since we want to make calculations based on the order to get
        // Real price values.
        $quote = $this->getOrder();

        // We use the base currency code.
        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();    
        $calculations->loadCurrencyCodes($quote,null,$currencyCode);
        
        // Calculate and get handlingfees.
        $handlingfees = $calculations->calculateHandlingfee( $quote, $this );
        
        // Is there more than one handlingfee?
        $moreThanOneHandlingfee = false;
        if(count($handlingfees) > 1)
            $moreThanOneHandlingfee = true;
        
        $calculations->setCurrentOrderIdToProcessing( $increment_id );
        $objectItems = $calculations->getQuoteItems( $quote );
        $resultArray = $calculations->generateValues( $quote, $this, $objectItems );
        
        $testMode = ($this->getConfigData('test') == '1' ? 'True' : 'False');
        $session = Mage::getSingleton('swphostedg/session');
        $session->setTestMode( $testMode );
        $session->setOrderRows( $resultArray );
        
        // localization parameters
        $storeLanguageCode = Mage::app()->getLocale()->getLocaleCode();
        $languageCodes     = explode('_', $storeLanguageCode);
        $language          = $languageCodes[0];
        
        $session->setCurrency( $currencyCode );
        $session->setLanguage( $language );
        $session->setOrderId ( $increment_id );
    }
    
    // here we set up the URL to send the order to the hosted solution
    public function getStandardCheckoutURL()
    {
        // Setup session information, needed by all requests.
        $parameters = "?paymentMethod=".$this->_paymentMethodName;
        return Mage::getUrl("swphostedg/request/index").$parameters;
    }
        
        // handle orders after return
    function completeFailed($orderId = null,$paymentText = null)
    {  
        $session = Mage::getSingleton('checkout/session');
        $orderId = ($orderId != null) ? $orderId : $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        
        if ($order && $order->getId())
        {
            if($order->getEmailSent() == false)
            {
                if($order->canCancel())
                {
                    $helper = Mage::helper("swpcommon");
                    if(!$helper->isVersionAboveOnePointThree())
                    {
                        $order->cancel();
                        $order->addStatusToHistory($order->getData("state"), $paymentText, true);
                    }
                }   
                $this->saveOrder($order);
            }
        }
        else
        {
            $order->registerCancellation($paymentText);
        }
        
        if($order)
            $this->saveOrder($order);
    }
    
    function completeCanceled($orderId = null,$paymentText)
    {
        $session = Mage::getSingleton('checkout/session');
        $orderId = ($orderId != null) ? $orderId : $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId( $orderId );
        
        if ($order && $order->getId())
        {
            $helper = Mage::helper('sveawebpay');
            $paymentText = $helper->__("Payment was canceled by user.");
            
            $helper = Mage::helper("swpcommon");
            if(!$helper->isVersionAboveOnePointThree())
            {
                $order->cancel();
                $order->addStatusToHistory($order->getData("state"), $paymentText, true);
            }
        }
        else
        {
            $order->registerCancellation($paymentText);
        }
        
        if($order)
            $this->saveOrder($order);
    }

    
    function completeReturn($orderId = null,$callback = false,$sveaOrderId = null)
    {
        $swpSession = Mage::getSingleton("swphostedg/session");
        $swpSession->setOrderIdLastCompleted($sveaOrderId);
       
        // If we have no orderid then we should no procceed.
        if($orderId == null)
            return;
        
        // Retrieve order.
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        // Update information for our order.
        if ($order && $order->getId() && $order->getEmailSent() != 1)
        {   
            // Check to see if customer has been notified, if so we skip this step.            
            if($order->getEmailSent() == false)
            { 
                $helper = Mage::helper('swphostedg');
                $paymentText   = $helper->__("Payment confirmed, ");

                $sveaTransactionIdStr = ($sveaOrderId !== null) ? " SveaWebPay Transaction ID: " . $sveaOrderId : "";            
                if($callback === true)
                    $paymentText  .= $helper->__("Payment method - Hosted 3.0 (Callback) %s",$this->_paymentMethodName) . $sveaTransactionIdStr;
                else
                    $paymentText  .= $helper->__("Payment method - Hosted 3.0 %s",$this->_paymentMethodName) . $sveaTransactionIdStr;
    
                // Send email and change sent flag.
                $order->sendNewOrderEmail();
                
                // This is to prevent that we don't sent the same mail once more.
                $order->setEmailSent(1);
                
                // Only change order status if we have changed order status.
                $newOrderStatus = $this->getConfigData('order_success_status');
                $order->setState($order->getState(), $newOrderStatus,$paymentText,true);
                $this->saveOrder($order);
            }

            // Get instances needed for removal of items in customers cart.
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $cart = Mage::getSingleton('checkout/cart');
            
            // Remove the whole shopping card.
            if ($quote != null && $cart != null)
            {
                foreach( $quote->getItemsCollection() as $item )
                    $cart->removeItem( $item->getId() );
                    
                // Save the cart after we've removed all items.
                $cart->save();
            }
        }
    }
    
    function saveOrder( $order )
    {
        $helper = Mage::helper("swpcommon");
        if(!$helper->isVersionAboveOnePointThree())
        {
            $calculations = Mage::helper("swpcommon/calculations");
            $handlingfee = $calculations->getHandlingfeeTotal( $order );
            if($handlingfee)
            {
                $handlingfeeValueIncl = $handlingfee["value"] + $handlingfee["tax"];
                $handlingfeeBaseValueIncl = $handlingfee["base_value"] + $handlingfee["tax"];
                $order->setGrandTotal( $order->getGrandTotal() + $handlingfeeValueIncl);
                $order->setBaseGrandTotal( $order->getBaseGrandTotal() + $handlingfeeBaseValueIncl );
                $order->setTaxAmount( $order->getTaxAmount() + $handlingfee["tax"] );
                $order->setBaseTaxAmount( $order->getBaseTaxAmount() + $handlingfee["base_tax"] );
            }
        }
        
        if($order)
            $order->save();
    }
}
?>