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
 
class SveaWebPay_Hosted_BaseController extends Mage_Core_Controller_Front_Action
{
    protected $_sendNewOrderEmail = true;

    private function getInnerRequestValue($key)
    {
        $request  = Mage::app()->getRequest();
        return ($request->isPost()) ? $request->getPost($key) : $request->getQuery($key);
    }
    
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    public function getBase()
    {
        return $this->getParentModel();
    }
    
    public function getParentModel($parent = 'base')
    {
        return Mage::getSingleton('sveawebpay/'.$parent);
    }
    
    // return from Hosted Solution
    public function responseAction()
    {
        $request = Mage::app()->getRequest();
        $session = $this->getCheckout();
        $order = Mage::getModel('sales/order');
        $order->load($session->getLastOrderId());
        // check md5 and payment method
        $paymentMethod = $this->validatePayment($order);
        
        // handle successful payments
        if ($paymentMethod != false && strtolower($this->getInnerRequestValue('Success')) == 'true')
        {
            // save billing/shipping information returned from invoice or payment plan, if set in config
            if (($paymentMethod == 'invoice') || ($paymentMethod == 'partpay'))
            {
                if($this->getParentModel($paymentMethod)->getConfigData('update_billing'))
                {
                    $address = Mage::getModel('sales/order_address')->load($order->billing_address_id);
                    $address->firstname = $this->getInnerRequestValue('Firstname');
                    $address->lastname  = $this->getInnerRequestValue('Lastname');
                    $address->street    = implode(', ', array($this->getInnerRequestValue('AddressLine1'),$this->getInnerRequestValue('AddressLine2')));
                    $address->city      = $this->getInnerRequestValue('PostArea');
                    $address->postcode  = $this->getInnerRequestValue('PostCode');
                    $address->telephone = $this->getInnerRequestValue('PhoneNumber');
                    $address->save();
                }
                if($this->getParentModel($paymentMethod)->getConfigData('update_shipping'))
                {
                    $address = Mage::getModel('sales/order_address')->load($order->shipping_address_id);
                    $address->firstname = $this->getInnerRequestValue('Firstname');
                    $address->lastname  = $this->getInnerRequestValue('Lastname');
                    $address->street    = implode(', ', array($this->getInnerRequestValue('AddressLine1'),$this->getInnerRequestValue('AddressLine2')));
                    $address->city      = $this->getInnerRequestValue('PostArea');
                    $address->postcode  = $this->getInnerRequestValue('PostCode');
                    $address->telephone = $this->getInnerRequestValue('PhoneNumber');
                    $address->save();
                }
            }
        
            $this->getParentModel($paymentMethod)->completeReturn();
            $this->_redirect('checkout/onepage/success', array('_secure'=>true));
        }
        // handle unsuccessful payments
        else
        {
            $errorCode = $this->getInnerRequestValue('ErrorCode');
            // cancelled payment
            if ($errorCode == 1)
            {
                $session->addMessage(Mage::getSingleton('core/message')->error($this->__("Payment process has been canceled.")));
                $this->getBase()->completeCanceled();
                // send back to cart
                $this->_redirect('checkout/cart', array('_secure'=>true));
            }
            // failed payment
            else
            {
                $error = Mage::getSingleton('sveawebpay/error');
                if($request->get("sroec") != "true")
                    $session->setErrorMessage(Mage::helper('sveawebpay')->__($error->convertFromCode($errorCode)));
                
                $this->getBase()->completeFailed();
                // send to failure page
                $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
            }
        }
    }
        
    private function validatePayment($order)
    {
        if (isset($_GET['MD5']))
        {
            $paymentMethod  = $this->getInnerRequestValue('PaymentMethod');
            $table = array (
                    'PARTPAYMENTSE' => 'partpay',
                    'SHBNP'         => 'partpay',
                    'KORTABSE'      => 'cc',
                    'KORTINDK'      => 'cc',
                    'KORTINFI'      => 'cc',
                    'KORTINNO'      => 'cc',
                    'KORTINSE'      => 'cc',
                    'NETELLER'      => 'cc',
                    'PAYSON'        => 'cc',
                    'EKOP'          => 'internet',
                    'AKTIA'         => 'internet',
                    'BANKAXNO'      => 'internet',
                    'FSPA'          => 'internet',
                    'GIROPAY'       => 'internet',
                    'NORDEADK'      => 'internet',
                    'NORDEAFI'      => 'internet',
                    'NORDEASE'      => 'internet',
                    'OP'            => 'internet',
                    'SAMPO'         => 'internet',
                    'SEBFTG'        => 'internet',
                    'SEBPRV'        => 'internet',
                    'SHB'           => 'internet',
                    'INVOICE'       => 'invoice',
                    'INVOICESE'     => 'invoice'
                );
            
            if (array_key_exists($paymentMethod,$table))
                $paymentMethod = $table[$paymentMethod];
            else
                return false;
            
            // md5 verification
            $rawQuery = explode('&MD5=', $_SERVER['QUERY_STRING']);
            $md5Check = $rawQuery[1];
            $queryString = $rawQuery[0];
            $returnedUrl = Mage::getUrl('sveawebpay/hosted/response',array('_secure' => true));
            
            if (strstr('?', $returnedUrl))
                $returnedUrl .= '&'.$queryString;
            else
                $returnedUrl .= '?'.$queryString;

            $password = $this->getParentModel($paymentMethod)->getConfigData('password');
            
            switch(true)
            {
                case ($md5Check == md5($returnedUrl.$password)):
                case ($md5Check == md5(urldecode(mb_convert_encoding($returnedUrl.$password,'utf-8')))):
                case ($md5Check == md5($returnedUrl . utf8_decode($password))):
                return $paymentMethod;
                break;
            }
        }
        return false;
    }
}