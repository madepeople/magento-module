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

class SveaWebPay_HostedG_CallbackController extends Mage_Core_Controller_Front_Action
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

    public function indexAction()
    {
        $response = $this->getInnerRequestValue("response");
        $mac      = $this->getInnerRequestValue("mac");
        
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
                            
                        $order = Mage::getModel('sales/order')->loadByIncrementId($response->customerRefno);
                        if($order)
                        {
                            // This is to prevent from doing this more than once.
                            $transaction = Mage::getModel("swpcommon/transactions");                        
                            if($transaction->saveTransaction($response->transactionId,$order->getId(),true,$order->getBaseGrandTotal()))
                            {
                                $paymentMethod->completeReturn($response->customerRefno,true,$response->transactionId);  
                            }
                        }
                    }
                }
            }
        }
    }
}