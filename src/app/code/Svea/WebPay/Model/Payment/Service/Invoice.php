<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_Payment_Service_Invoice
    extends Svea_WebPay_Model_Payment_Service_Abstract
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
        $svea = $this->_initializeSveaOrder($payment->getOrder());
        $request = $svea->useInvoicePayment();

        $this->_validateAmount($svea, $amount);

        $response = $request->doRequest();
        if ($response->accepted == 1) {
            $rawDetails = array();
            foreach ($response as $key => $val) {
                if (!is_string($key) || is_object($val)) {
                    continue;
                }
                $rawDetails[$key] = $val;
            }
            $payment->setTransactionId($response->sveaOrderId)
                    ->setIsTransactionClosed(false)
                    ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $rawDetails);
        } else {
            $errorMessage = $response->errormessage;
            $statusCode = $response->resultcode;
            $errorTranslated = Mage::helper('svea_webpay')->responseCodes($statusCode, $errorMessage);

            throw new Mage_Payment_Exeption($errorTranslated);
        }

        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        throw new Exception('implement me');
        return parent::capture($payment, $amount);
    }

    public function refund(Varien_Object $payment, $amount)
    {
        throw new Exception('implement me');
        return parent::refund($payment, $amount);
    }

    public function void(Varien_Object $payment)
    {
        throw new Exception('implement me');
        return parent::void($payment);
    }
}