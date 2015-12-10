<?php

class Svea_WebPay_Model_Resource_Customer_Address extends Mage_Core_Model_Mysql4_Abstract
{

    protected function _construct()
    {
        $this->_init('svea_webpay/customer_address', 'id');
    }

}
