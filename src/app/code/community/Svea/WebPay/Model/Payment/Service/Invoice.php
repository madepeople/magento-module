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


}