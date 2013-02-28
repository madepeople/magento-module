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
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaPaymentRequest.php");
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/SveaOrderRow.php");

class SveaWebPay_HostedG_RequestController extends Mage_Core_Controller_Front_Action
{
    private function getInnerRequestValue($key)
    {
        $request  = Mage::app()->getRequest();
        return ($request->isPost()) ? $request->getPost($key) : $request->getQuery($key);
    }
    
    private function getMethodByName($name = null)
    {
        return ($name !== null) ? Mage::getModel("swphostedg/" . $name) : null;
    }
    
    public function indexAction()
    {
        $method = $this->getInnerRequestValue("paymentMethod");
        $methodModel = $this->getMethodByName($method);
        if(!$methodModel)
            return false;

        $merchantId = $methodModel->getConfigData("merchant_id");
        $secret = $methodModel->getConfigData("merchant_secret_word");
        $testMode = $methodModel->getConfigData("test");
        
        $url = ($testMode == true) ? SveaConfig::SWP_TEST_URL : SveaConfig::SWP_PROD_URL;
        
        $config = SveaConfig::getConfig();
        $config->setTestMode( $testMode );
        $config->merchantId = $merchantId;
        $config->secret     = $secret;
        
        switch(strtolower($method))
        {
            // Card execute cardpayment request.
            case "card":
                $method = Mage::getModel("swphostedg/card");
                if($method)
                {
                    $method->prepare();
                    $this->request($config,$url,SveaOrder::CARD,false);
                }
            break;
            
            // ALL execute all methods.
            case "all":
                $method = Mage::getModel("swphostedg/all");
                if($method)
                {
                    $method->prepare();
                    $this->request($config,$url,null,true);
                }
                
            break;
            
            // Default fallback
            default:
                echo "No selected paymentmethod.";
                break;
        }
    }
        
    function changeOrderState($order)
    {
        //$order->setState(Mage_Sales_Model_Order::STATE_CANCELED);
        //$order->save();
    }

    private function request($config,$url,$method = null,$useExclude = false)
    {
        $session = Mage::getSingleton('swphostedg/session');
        
        $paymentRequest = new SveaPaymentRequest($config->merchantId,$config->secret);
        $sveaOrder = new SveaOrder();
        
        $orderRows = $session->getOrderRows();
        
        $totalPrice = 0;
        $totalVat   = 0;
        
        //Add order rows
        foreach ($orderRows as $order)
        {
            $tax          = $order['tax'];
            $productVat   = $order['price'] * ($tax / 100);
            //$productVat   = round($productVat,2);
            $productPrice = $order['price'] +  $productVat;
            //$productPrice = round($productPrice,2);
            $orderRow              = new SveaOrderRow();
            $orderRow->amount      = number_format($productPrice,2,'','');
            $orderRow->vat         = number_format($productVat,2,'','');
            //$orderRow->description = $or['title'];
            $orderRow->name        = $order['name'];
            $orderRow->quantity    = $order['qty'];
            //$orderRow->sku         = "1";
            $orderRow->unit        = "st";
            $sveaOrder->addOrderRow($orderRow);
            
            $totalPrice += number_format(($productPrice * $orderRow->quantity),2,'','');
            $totalVat   += number_format(($productVat   * $orderRow->quantity),2,'','');
        }
        
        $sveaOrder->setParam("callbackurl",Mage::getUrl("swphostedg/callback/index"));
        
        $sveaOrder->amount        = $totalPrice;
        $sveaOrder->customerRefno = $session->getOrderId();
        $sveaOrder->returnUrl     = Mage::getUrl("swphostedg/response/index");
        $sveaOrder->vat           = $totalVat;
        $sveaOrder->currency      = $session->getCurrency();
        $paymentRequest->order    = $sveaOrder;
        
        if($method !== null)
            $sveaOrder->paymentMethod = $method;
        
        $urlToThisShop = Mage::getUrl("swphostedg/response/cancel");
        $sveaOrder->setParam("Cancelurl",$urlToThisShop);
        
        $isoCode = Mage::app()->getLocale()->getLocale();
        $parts = explode("_", $isoCode);
        $language = $parts[0];
    
        // Get supported language.
        $language = strtolower($language);
        $swpHostedG = Mage::helper("swphostedg");
        $language = $swpHostedG->getSupportedLanguageCode( $language );
        $sveaOrder->setParam("lang",$language);
        
        $session = Mage::getSingleton('checkout/session');
        $order = $session->getQuote();
                
        $this->changeOrderState($order);
        if($useExclude)
        {
            $methodModel = $this->getMethodByName( "all");
            if($methodModel !== null)
            {
                // These are not to be used in the paypage when using the all => internet payment
                $sveaOrder->excludePaymentMethods = Array("SVEAINVOICESE","SVEASPLITSE","CARD");
            }
        }
        
        $paymentRequest->createPaymentMessage();
        $this->renderPaymentRequest( $paymentRequest,$url );
    }
    
    private function renderPaymentRequest( $paymentRequest,$url )
    {
        echo "<noscript>Eftersom du inte har javascript aktiverat blir du tvungen att klicka på betala knappen för att fortsätta.</noscript>";
        echo "<form name='paymentform' action='".$url."' method='post'>";
        echo "<input type='hidden' name='merchantid' value='".$paymentRequest->merchantid."'/>";
        echo "<input type='hidden' name='message' value='".$paymentRequest->payment."'/>";
        echo "<input type='hidden' name='mac' value='".$paymentRequest->mac."'/>";
        echo "<noscript>";
        echo "<input type='submit' value=\"Betala\" />";
        echo "</noscript>";
        echo "</form>";
        echo "<script language=\"javascript\">document.forms[0].submit();</script>";
    }
    
    private function validatePayment($order)
    {
        return true;
    }
}