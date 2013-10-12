<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_Payment_Service_Invoice extends Svea_WebPay_Model_Payment_Abstract
{
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canManageRecurringProfiles = false;

    protected $_code = 'svea_invoice';
    protected $_formBlockType = 'svea_webpay/payment_service_invoice';

    public function authorize(Varien_Object $payment, $amount)
    {
        return parent::authorize($payment, $amount);
    }

    public function capture(Varien_Object $payment, $amount)
    {
        return parent::capture($payment, $amount);
    }

    public function refund(Varien_Object $payment, $amount)
    {
        return parent::refund($payment, $amount);
    }

    public function void(Varien_Object $payment)
    {
        return parent::void($payment);
    }
}