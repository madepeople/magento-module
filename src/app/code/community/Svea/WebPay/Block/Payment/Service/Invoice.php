<?php

/**
 * Invoice information block used in the checkout process
 */
class Svea_WebPay_Block_Payment_Service_Invoice
    extends Svea_WebPay_Block_Payment_Service_Abstract
{

    protected $_hasLogo = true;

    protected function _construct()
    {
        $this->setData('template', 'svea/payment/service/invoice.phtml');
        return parent::_construct();
    }

}