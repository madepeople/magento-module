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
 
class SveaWebPay_Hosted_Model_Base extends Mage_Payment_Model_Method_Abstract
{
    private function getInnerRequestValue($key)
    {
        $request  = Mage::app()->getRequest();
        return ($request->isPost()) ? $request->getPost($key) : $request->getQuery($key);
    }
    
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
    
    protected $_formBlockType = 'sveawebpay/form';
    
    public function __construct()
    {
        $helper = Mage::helper("swpcommon");
        if(!$helper->isVersionAboveOnePointThree())
            $this->_canCapturePartial = false;
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
    
    // here we set up the URL to send the order to the hosted solution
    public function getStandardCheckoutURL()
    {
        // If we do not have the mbstring loaded we must let the user know this and not continue.
        if(!in_array("mbstring",get_loaded_extensions()))
        {
            $helper = Mage::helper("sveawebpay");
            $checkoutSession = Mage::getSingleton("checkout/session");
            $checkoutSession->setErrorMessage($helper->__("Mbstring extension not loaded."));
            return Mage::getUrl("swphosted/base/response")."?sroec=true";
        }
        
        $calculations = Mage::helper("swpcommon/calculations");        
        $quote = $this->getQuote();
        // Calculate and get handlingfees.
        $handlingfees = $calculations->calculateHandlingfee( $quote, $this );
        
        // Is there more than one handlingfee?
        $moreThanOneHandlingfee = false;
        if(count($handlingfees) > 1)
            $moreThanOneHandlingfee = true;
        
        $quote = $this->getQuote();
        $order = $quote->getOrder();
        
        $calculations->setCurrentOrderIdToProcessing( $quote->getReservedOrderId() );
        $objectItems = $calculations->getQuoteItems( $quote );
        $resultArray = $calculations->generateValues( $quote, $this, $objectItems );
       
        // This is a hardcoded value.
        $currencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        
        // localization parameters
        $storeLanguageCode = Mage::app()->getLocale()->getLocaleCode();
        $lngCodes = explode('_', $storeLanguageCode);
        $language = $lngCodes[0];
        $country = $quote->getBillingAddress()->getCountry();
        
        $parameters = array(
            'Username'      =>  $this->getConfigData('username'),
            'OrderId'       =>  $this->getQuote()->getReservedOrderId(),
            'ResponseURL'   =>  Mage::getUrl('sveawebpay/hosted/response',array('_secure' => true)),
            'Testmode'      =>  ($this->getConfigData('test') == '1' ? 'True' : 'False'),
            'Paymentmethod' =>  $this->_transactionType,
            'Currency'      =>  $currencyCode,
            'Language'      =>  $language,
            'Country'       =>  $country,
            'Version'       =>  2,
            'Module'        =>  'Magento3343');
        
        $counter = 1;
        foreach($resultArray as $result)
        {
            $parameters = array_merge($parameters,
                    array(
                        'Row'.$counter.'AmountExVAT'   => $result["price"],
                        'Row'.$counter.'Description'   => $result["name"],
                        'Row'.$counter.'Quantity'      => $result["qty"],
                        'Row'.$counter.'VATPercentage' => $result["tax"]
                    )
                );
            $counter++;
        }
        
        foreach($parameters as $key => $value)
            $parametersArray[] = $key.'='.urlencode(strip_tags($value));
        
        $helperUrl = Mage::helper("swpcommon/url");
        $url = $helperUrl->getHosted(); 
        
        // MD5 Check for hosted to see its correct parameters.
        $md5Check = $url . '?'.mb_convert_encoding(implode('&', $parametersArray), 'utf-8');
        $md5 = md5($md5Check.$this->getConfigData('password'));
        return $md5Check.'&md5='.$md5;
    }
    
    // handle orders after return
    function completeFailed()
    {
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order')->load($session->getLastOrderId());
        
        if ($order && $order->getId())
        {
            $helper = Mage::helper('sveawebpay');
            $paymentText = $helper->__("Payment failed, ");
            $secnumber = $this->getInnerRequestValue('SecurityNumber');
            
            if ($secnumber != "")
                $paymentText .= $helper->__("Security Number - %s, ",$secnumber);
                
            $paymentMethod = $this->getInnerRequestValue('PaymentMethod');
            if ($paymentMethod != "")
                $paymentText .= $helper->__("Payment method - %s",$paymentMethod);
            else
                $paymentText = substr($paymentText,0,-2);
            
            $commonHelper = Mage::helper("swpcommon");
            if(!$commonHelper->isVersionAboveOnePointThree())
            {
                $order->cancel();
                $order->addStatusToHistory($order->getData("state"), $paymentText, true);
            }
            else
                $order->registerCancellation($paymentText);
        }
        
        if($order)
            $this->saveOrder( $order );
    }
    
    function completeCanceled()
    {
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order')->load($session->getLastOrderId());
        if ($order->getId())
        {
            $helper = Mage::helper('sveawebpay');
            $paymentText = $helper->__("Payment was canceled by user.");
            $commonHelper = Mage::helper("swpcommon");
            if(!$commonHelper->isVersionAboveOnePointThree())
            {
                $order->cancel();
                $order->addStatusToHistory($order->getData("state"), $paymentText, true);
            }
            else
                $order->registerCancellation($paymentText);
        }

        if($order)
            $this->saveOrder( $order );
    }
    
    function completeReturn()
    {
        $session = Mage::getSingleton('checkout/session');
        $order = Mage::getModel('sales/order')->load($session->getLastOrderId());
        
        if ($order->getId())
        {   
            $helper = Mage::helper('sveawebpay');
            $paymentText = $helper->__("Payment confirmed, ");
            $secnumber = $this->getInnerRequestValue('SecurityNumber');
            if ($secnumber != "")
                $paymentText .= $helper->__("Security Number - %s, ",$secnumber);
            
            $paymentMethod = $this->getInnerRequestValue('PaymentMethod');
            $paymentText .= $helper->__("Payment method - %s",$paymentMethod);
        
            // Send email and change sent flag.
            $order->sendNewOrderEmail();

            // Only change order status if we have changed order status.
            $newOrderStatus = $this->getConfigData('order_success_status');
            $order->setState($order->getState(), $newOrderStatus,$paymentText,true);

            $cart  = Mage::getSingleton('checkout/cart');
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            
            if($quote != null)
                foreach( $quote->getItemsCollection() as $item )
                    $cart->removeItem( $item->getId() );
                    
            $cart->save();
            $this->saveOrder( $order );
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
        $order->save();
    }
}
?>