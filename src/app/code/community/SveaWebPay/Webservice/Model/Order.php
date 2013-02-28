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

class SveaWebPay_Webservice_Model_Order extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init("sveawebpayws/order");
    }
    
    /**
    * 
    * Save a new order with information.
    * @param int $sveaOrderId Number retrived from sveawebservice when requestion CreateOrder.
    * @param int $orderId ID of Magento's own order object.
    * @return bool True if succeded false otherwise.
    */
    public function saveNewOrderInformation($sveaOrderId,$orderId)
    {
        $log = Mage::helper("swpcommon/log");
        try
        {
            $order = Mage::getModel("sveawebpayws/order");
            
            $order->setSveaOrderId ( $sveaOrderId );
            $order->setOrderId     ( $orderId     );
            $order->save();
        }
        catch( Exception $e )
        {
            $log->log( "Exception in Sveawebpay Webservice order class: ".$e->getMessage() );
            return false;
        }
        return true;
    }
    
    /**
    * 
    * Loads a webservice order by ID of a Magento order object.  
    * @param int $orderId
    * @return Sveawebpay_Webservice_Model_Order Order object, otherwise null.
    */
    public function loadByOrderId( $orderId )
    {
        return $this->getCollection()->addFilter( "order_id",$orderId )->load()->getFirstItem();
    }
    
    /**
    * 
    * Get Sveawebpay Webservice Order by ID of a Magento Order object (Mage_Sales_Model_Order).
    * @param  int $orderId Id of a Magento order object.
    * @return Sveawebpay_Webservice_Model_Order Otherwise null.
    */
    public function getSWPWSOrderByOrderId( $orderId )
    {
        $log = Mage::helper("swpcommon/log");
        $collection = $this->getCollection()->addFieldToFilter("order_id",$orderId)->load();
        
        try
        {
            $resultArray = $collection->getData();
        }
        catch( Exception $e )
        {
            $log->log("Exception in SveaWebPay Webservice Order Model: ".$e->getMessage());
            return null;
        }
        
        if(is_array($resultArray) == false || empty($resultArray) == true )
            return null;
        
        if(is_array($resultArray[0]) == false || key_exists("svea_order_id",$resultArray[0]) == false)
            return null;
        
        return $resultArray[0];
    }
    
    /**
    * 
    * Retrives Sveawebpay Webservice order id by Magento's order id.
    * @param int $orderId Magento's order id.
    * @return int Id of Sveawebpay Webservice order id. Null otherwise.
    */
    public function getSWPWSOrderIdByOrderId( $orderId )
    {
        $log = Mage::helper("swpcommon/log");
        $collection = $this->getCollection()->addFieldToFilter("order_id",$orderId)->load();

        try
        {
            $resultArray = $collection->getData();
        }
        
        catch( Exception $e )
        {
            $log->log("Exception in SveaWebPay Webservice Order Model: ".$e->getMessage());
            return null;
        }
        
        if(is_array($resultArray) == false || empty($resultArray) == true )
            return null;
        
        if(is_array($resultArray[0]) == false || key_exists("svea_order_id",$resultArray[0]) == false)
            return null;
        
        return $resultArray[0]["id"];
    }
    
    /**
    * 
    * Get Svea order number, this is needed by Soap request CreateInvoice.
    * @param int $orderId Magento's order Id.
    * @return int SveaOrderId(number). Null otherwise.
    */
    public function getSveaOrderIdByOrderId( $orderId )
    {
        $log = Mage::helper("swpcommon/log");
        $collection = $this->getCollection()->addFieldToFilter("order_id",$orderId)->load();
        
        try
        {
            $resultArray = $collection->getData();
        }
        catch( Exception $e )
        {
            $log->log("Exception in SveaWebPay Webservice Order Model: ".$e->getMessage());
        }
        
        if(is_array($resultArray) == false || empty($resultArray) == true )
            return null;
        
        if(is_array($resultArray[0]) == false || key_exists("svea_order_id",$resultArray[0]) == false)
            return null;

        return $resultArray[0]["svea_order_id"];
    }
  
}