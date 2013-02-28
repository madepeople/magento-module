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

class SveaWebPay_Webservice_Model_Observer_Order
{
    private function isMethodActive( $observer )
    {
        $event = $observer->getEvent();
        if(!$event)
            return false;
        
        $order = $event->getOrder();
        if(!$order)
            return false;
        
        $helper  = Mage::helper("swpcommon");
        $model   = Mage::getModel('sveawebpayws/source_methods');
        $methods = $model->getPaymentMethods();
        
        if(!$helper->isMethodActive($methods,$order))
            return false;  
        return true;  
    }
        
    public function retrieve( $observer )
    {
        if(!$this->isMethodActive( $observer ))
            return false;
        
        $event = $observer->getEvent();
        $order = $event->getOrder();
        
        if($order)
        {
			$payment = $order->getPayment();
			if($payment)
			{
				$method = $payment->getMethodInstance();
				if($method)
				{
					$method->retrieveOrderInformation( $order );
					$method->prepareOrderItems( $order );
				}
			}
        }
		
            
        return true;
    }

    public function create( $observer )
    {
        if(!$this->isMethodActive( $observer ))
            return false;
        
        $event = $observer->getEvent();
        $order = $event->getOrder();
        
        if($order)
        {
			$payment = $order->getPayment();
			if($payment)
			{
				$method = $payment->getMethodInstance();
				if($method)
				{
					return $method->execute( $order );
				}
			}
        }
        return false;
    } 
}