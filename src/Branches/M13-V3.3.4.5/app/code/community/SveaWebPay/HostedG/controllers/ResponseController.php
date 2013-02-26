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
 
// Autoload doesn't work therefore we need to include all files.
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaXMLBuilder.php");
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaConfig.php");
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaOrder.php");
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaPaymentResponse.php");
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaOrderRow.php");

class SveaWebPay_HostedG_ResponseController extends Mage_Core_Controller_Front_Action
{
    private function getPaymentMethodBySveaEnum($paymentMethod)
    {
        $modelName = null;
        switch($paymentMethod)
        {
            case SveaOrder::CARD:
                $modelName = "card";
                break;
            
            default:
                $modelName = "all";
                break;
        }
        return ($modelName !== null) ? Mage::getModel("swphostedg/".$modelName) : null;
    }
    
    private function getInnerRequestValue($key)
    {
        $request  = Mage::app()->getRequest();
        return ($request->isPost()) ? $request->getPost($key) : $request->getQuery($key);
    }
    
    public function cancelAction()
    {
        $customerRefNr = $this->getInnerRequestValue("customerrefno");
        if($customerRefNr != null)
        {
            $method = $this->getPaymentMethodBySveaEnum("all");
            if($method)
            {
                $method->errorMessage("SveaWebPayHostedStatusCode");
                $method->completeCanceled($customerRefNr,"Customer choose to cancel payment.");
            }
        }
        // Still want to be redirected to fail page.
        $this->proccessResponse();
    }
    
    public function indexAction()
    {    
        $this->proccessResponse();
    }
    
    public function proccessResponse()
    {
        $response = $this->getInnerRequestValue("response");
        $mac      = $this->getInnerRequestValue("mac");
        
        $failed = true;
        if($response != null && $mac != null)
        {
            $response = new SveaPaymentResponse( $response );
            $paymentMethod = $this->getPaymentMethodBySveaEnum( $response->paymentMethod );
            if($paymentMethod !== null)
            {
                $secret = $paymentMethod->getConfigData("merchant_secret_word");
                if($response->validateMac($mac,$secret) == true)
                {
                    if($response->statuscode == 0)
                    {
                        // Update shipping and billing information depending on our response.
                        if($response->paymentMethod == SveaOrder::SVEAINVOICESE || $response->paymentMethod == SveaOrder::SVEASPLITSE)
                            $paymentMethod->updateShippingAndBilling($response);
                            
                        $paymentMethod->completeReturn($response->customerRefno,false,$response->transactionId);
                        // Reset the shopping cart, since we successfully bought our products.
                        $this->resetCart();
                        $failed = false;
                    }
                    else
                    {
                        $errorMessage = "SveaWebPayHostedStatusCode".$response->statuscode;
                        $paymentMethod->errorMessage($errorMessage);
                        $paymentMethod->completeFailed($response->customerRefno);   
                    }
                }
                else
                {
                    $paymentMethod->completeFailed();
                }
            }
        }
        
        if($failed == true)
        {
            $this->_redirect("checkout/onepage/failure",array("secure" => true));
        }
        else
        {
            $this->_redirect("checkout/onepage/success",array("secure" => true));
        }
    }
    
    // Remove the whole shopping card.
    private function resetCart()
    {
        $cart  = Mage::getSingleton('checkout/cart');
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        
        if ($quote != null)
        {
            foreach( $quote->getItemsCollection() as $item )
            {
                $cart->removeItem( $item->getId() );
            }
            $cart->save();
        }
    }
}