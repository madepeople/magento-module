<?php

class Svea_Webpay_Model_Resource_Paymentplan_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('svea_webpay/paymentplan');
    }
}
