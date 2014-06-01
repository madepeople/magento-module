<?php

/**
 * Invoice information block used in the checkout process
 */
class Svea_WebPay_Block_Payment_Service_Invoice
    extends Svea_WebPay_Block_Payment_Service_Abstract
{
    protected $_logoCode = 'svea_invoice';

    protected function _construct()
    {
        $invoiceInfo = trim(Mage::getStoreConfig('payment/svea_invoice/invoice_info'));
        if (!empty($invoiceInfo)) {
            if (Mage::helper('svea_webpay')->usingQuickCheckout()) {
                $this->setData('template', 'svea/payment/service/invoice.phtml');
            }
        }
        return parent::_construct();
    }
}
