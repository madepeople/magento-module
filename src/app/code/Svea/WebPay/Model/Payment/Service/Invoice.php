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

    protected function _sveaResponseToArray($response)
    {
        $result = array();
        foreach ($response as $key => $val) {
            if (!is_string($key) || is_object($val)) {
                continue;
            }
            $result[$key] = $val;
        }
        return $result;
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $sveaConfig = $this->_getSveaConfig();
        $svea = WebPay::createOrder($sveaConfig);
        $order = $payment->getOrder();

        $this->_initializeSveaOrder($svea, $order);
        $this->_addItems($svea, $order);
        $this->_addTotals($svea, $order);
        $this->_validateAmount($svea, $amount);

        $request = $svea->useInvoicePayment();
        $response = $request->doRequest();

        if ($response->accepted == 1) {
            $rawDetails = $this->_sveaResponseToArray($response);
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
        $sveaOrderId = $payment->getParentTransactionId();
        if (empty($sveaOrderId)) {
            Mage::throwException('Missing Svea invoice id, cannot capture');
        }

        $order = $payment->getOrder();
        $invoice = $order->getCurrentInvoice();

        $sveaConfig = $this->_getSveaConfig();
        $svea = WebPay::deliverOrder($sveaConfig);

        $this->_initializeSveaOrder($svea, $invoice);
        $this->_addItems($svea, $invoice);
        $this->_addTotals($svea, $invoice);
        $this->_validateAmount($svea, $amount);

        $svea->setInvoiceDistributionType($this->getConfigData('distribution_type'));
        $svea->setOrderId($sveaOrderId);

        $response = $svea->deliverInvoiceOrder()
                ->doRequest();

        if ($response->accepted == 1) {
            $rawDetails = $this->_sveaResponseToArray($response);
            $payment->setTransactionId($response->invoiceId)
                    ->setIsTransactionClosed(false)
                    ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $rawDetails);
        } else {
            $errorTranslated = Mage::helper('svea_webpay')->getErrorMessage(
                    $response->resultcode,
                    $response->errormessage);

            Mage::throwException($errorTranslated);
        }

        return $this;
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