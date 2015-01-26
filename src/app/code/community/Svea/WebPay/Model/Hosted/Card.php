<?php

/**
 * Finish Svea Payment Object for Card specific values
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

class Svea_WebPay_Model_Hosted_Card extends Svea_WebPay_Model_Hosted_Abstract
{

    /**
     * The reason we can't capture partial at this time is because capture is
     * done on order row level and not stricly an amount of the authorized total
     *
     * If we can come up with a way to match svea order rows with magento rows,
     * we should be able to cpture partial. SKUs can be matched, but third
     * party modules for discounts, gift cards, wrapping etc make things hard.
     *
     * @var bool
     */
    protected $_canCapturePartial = false;

    protected $_canVoid = true;

    protected $_code = 'svea_cardpayment';
    protected $_sveaUrl = 'svea_webpay/hosted/redirect';
    protected $_formBlockType = 'svea_webpay/payment_hosted_card';

    /**
     *
     * @param type $sveaObject
     * @param type $addressSelector
     * @return type Svea CreateOrder
     */
    protected function _choosePayment($sveaObject, $addressSelector = NULL)
    {
        // In Denmark there might be other card choices. May not be necessary
        // in future updates to Svea's systems
        if (!isset($sveaObject->countryCode) || $sveaObject->countryCode == "DK") {
            $sveaObject = $sveaObject->usePayPageCardOnly()
                ->setPayPageLanguage(Mage::helper('svea_webpay')->__('lang_code'));
        } else {
            $sveaObject = $sveaObject->usePaymentMethod(PaymentMethod::KORTCERT);
        }

        $paymentFormPrep = $sveaObject->setReturnUrl(Mage::getUrl("svea_webpay/hosted/return"))
            ->setCallbackUrl(Mage::getUrl('svea_webpay/hosted/callback'))
            ->setCancelUrl(Mage::getUrl("svea_webpay/hosted/cancel"));

        return $paymentFormPrep;
    }

    /**
     * Capture (confirm) an open transaction ('AUTHORIZED') at svea. We use the
     * ConfirmTransaction class directly because the operation is simple.
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this|void
     * @throws Mage_Payment_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $sveaOrderId = $payment->getParentTransactionId();
        if (null === $sveaOrderId) {
            // If there is no previous authorization
            $sveaOrderId = $payment->getTransactionId();
        }
        $information = $this->fetchTransactionInfo($payment, $sveaOrderId);

        if ($information['status'] !== 'AUTHORIZED') {
            $message = 'Payment cannot be captured: ';
            if (!empty($information['errorMessage'])) {
                $message .= $information['errorMessage'] . ' (' . $information['resultcode'] . ')';
            } else {
                $message .= $information['status'];
            }
            $message .= '.';
            throw new Mage_Payment_Exception($message);
        }

        $order = $payment->getOrder();
        $paymentMethodConfig = $this->getSveaStoreConfClass($order->getStoreId());
        $config = new SveaMageConfigProvider($paymentMethodConfig);

        $countryId = $order->getBillingAddress()->getCountryId();

        $confirmTransactionRequest = new Svea\HostedService\ConfirmTransaction($config);
        $confirmTransactionRequest->countryCode = $countryId;
        $confirmTransactionRequest->transactionId = $sveaOrderId;

        $defaultCaptureDate = explode('T', date('c')); // [0] contains date part
        $confirmTransactionRequest->captureDate = $defaultCaptureDate[0];
        $response = $confirmTransactionRequest->doRequest();

        if ($response->accepted !== 1) {
            $message = 'Capture failed for transaction ' . $sveaOrderId . ': ' . $response->errormessage . ' (' . $response->resultcode . ')';
            throw new Mage_Payment_Exception($message);
        }

        $result = $this->_flatten($response);
        $payment->setIsTransactionClosed(true)
            ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $result);

        return $this;
    }

    /**
     * Fetch transaction info
     *
     * @param Mage_Payment_Model_Info $payment
     * @param string $transactionId
     * @return array
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId)
    {
        if (preg_match('/-/', $transactionId)) {
            Mage::throwException('Cannot fetch transaction information for child transactions. Please use the parent transaction.');
        }

        $request = $this->_getQueryOrderRequest($payment, $transactionId);
        $response = $request->queryCardOrder()
            ->doRequest();

        $result = $this->_flatten($response);
        return $result;
    }

    public function void(Varien_Object $payment)
    {
        return $this->cancel($payment);
    }

    public function cancel(Varien_Object $payment)
    {
        $sveaOrderId = $payment->getParentTransactionId();
        $request = $this->_getCancelOrderRequest($payment, $sveaOrderId);
        $response = $request->cancelCardOrder()->doRequest();

        if ($response->accepted === 0) {
            $message = 'cancelCardOrder failed for transaction ' . $sveaOrderId . ': ' . $response->errormessage . ' (' . $response->resultcode . ')';
            Mage::throwException($message);
        }

        $result = $this->_flatten($response);
        $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $result);

        return $this;
    }

}