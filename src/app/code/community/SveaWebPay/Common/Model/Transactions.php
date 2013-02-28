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

class SveaWebPay_Common_Model_Transactions extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('swpcommon/transactions');
    }
	
    public function isCustomerNotifiedByTransactionId($transactionId)
    {
        
    }
    
    public function existsByTransactionId($transactionId)
    {
        $transactionModel = Mage::getModel("swpcommon/transactions");
        try
        {
            $collection = $this->getCollection()->addFieldToFilter("transaction_id",$transactionId)->load();
            if($collection->getSize() > 0)
                return true;
        }
        catch(Exception $exception)
        {
            return false;
        }
        return false;
    }
    
    public function saveTransaction($transactionId,$orderId,$customerNotified,$amount)
    {
        $log = Mage::helper("swpcommon/log");
        $storeId = Mage::app()->getStore()->getRootCategoryId();
		
        if($this->existsByTransactionId($transactionId))
        {
           $log->log("Transaction already exists. TransactionID: " . $transactionId);
           return false; 
        }
        
		try
		{
			$transaction = Mage::getModel("swpcommon/transactions");
			$transaction->setTransactionId($transactionId);
            $transaction->setOrderId($orderId);
            $transaction->setStoreId($storeId);
			$transaction->setCustomerNotified($customerNotified);
            $transaction->setAmount($amount);
			$transaction->save();
		}
		catch(Exception $exception)
		{
			$log->exception("Exception caught while saving transaction. Exception given: " . $exception->getMessage());
			return false;
		}
        return true;
    }
}