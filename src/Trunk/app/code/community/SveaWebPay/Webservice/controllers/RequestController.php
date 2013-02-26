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
 
class SveaWebPay_Webservice_RequestController extends Mage_Core_Controller_Front_Action
{   
	private function getOrder()
    {
		$order = null;

        $session = Mage::getSingleton("checkout/session");
		$increment_id = $session->getData('last_real_order_id');

		if($increment_id)
		{
			$order = Mage::getModel('sales/order')
				->loadByIncrementId($increment_id);

			if(is_null($order->getId()))
			{
				//$this->order = null;
				$order = null;
			}
		}
			
        return $order;//$this->getCheckout()->getQuote();
    }
    
    public function partpayAction()
    {
        $method = Mage::getModel("sveawebpayws/paymentmethod_partpay");
        $this->execution($method);
        
    }

    public function invoiceAction()
    {
        $method = Mage::getModel("sveawebpayws/paymentmethod_invoice");
        $this->execution($method);
        
    }

    protected function execution($method = null)
    {
        if($method === null)
            return false;
        
        
        $sessionws = Mage::getSingleton("sveawebpayws/session");
        $session = Mage::getSingleton("checkout/session");
//		$increment_id = $session->getData('last_real_order_id');
//        $sessionws->setReservedOrderId( $increment_id );
//
        //$method->prepareOrderItems($this->getOrder(),$increment_id);
        //$method->execute($this->getOrder());
            
        return true;
    }
}

?>