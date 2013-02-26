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
 
class SveaWebPay_Hosted_Model_Observer_Invoice
{
    public function save( $observer )
    {
        $helper  = Mage::helper("swpcommon");
        $model   = Mage::getModel("sveawebpay/source_methods");
        $methods = $model->getPaymentMethods();
        
        if(!$helper->isVersionAboveOnePointThree())
            return $this;
        
        $event   = $observer->getEvent();
        $invoice = $event->getInvoice();
        
        if(!$invoice)
            return $this;
        
        $order = $invoice->getOrder();
        if(!$order)
            return $this;
        
        if(!$helper->isMethodActive($methods,$order))
            return $this;
        
        if(!$helper->isHandlingfeeEnabled($methods,$order))
            return $this;
        
        $calculations = Mage::helper("swpcommon/calculations");
        if($calculations->isHandlingfeeInvoiced($order))
            return $this;
        
        $handlingfeestore = Mage::getModel("swpcommon/handlingfeestore");
        $collection = $handlingfeestore->getCollection()->addFilter("order_id",$order->getIncrementId());
        $collection->load();
        
        foreach($collection as $node)
        {
            $node->setInvoiceId( $invoice->getIncrementId() );
            $node->save();
        }
        
        $collection->save();
        return $this;
    }
}