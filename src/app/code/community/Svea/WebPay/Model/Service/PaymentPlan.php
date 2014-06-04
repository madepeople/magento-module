<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

/**
 * Class for PaymentPlan specifics
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Model_Service_PaymentPlan extends Svea_WebPay_Model_Service_Abstract
{
    protected $_code = 'svea_paymentplan';
    protected $_formBlockType = 'svea_webpay/payment_service_paymentPlan';
    protected $_canCapturePartial = false;

    /**
     * Take the PaymentPlan path in Create Order object
     *
     * @param type $sveaObject
     * @return type
     */
    protected function _choosePayment($sveaObject)
    {
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $order = $paymentInfo->getOrder();
        } else {
            $order = $paymentInfo->getQuote();
        }
        $object = $sveaObject->usePaymentPlanPayment($paymentInfo->getAdditionalInformation('campaign'));
        return $object;
    }

    /**
     * For Svea, Deliver order
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return type
     */
    public function capture(Varien_Object $payment, $amount)
    {
        //Alternative: $sveaOrderId = $payment->getTransactionId(), comes with -capture
        $sveaOrderId = $this->getInfoInstance()
                ->getAdditionalInformation('svea_order_id');
        if (empty($sveaOrderId)) {
            if (!$this->getConfigData('autodeliver')) {
                $errorTranslated = Mage::helper('svea_webpay')->responseCodes("", 'no_orderid');
                Mage::throwException($errorTranslated);
            }
            $sveaOrderId = $this->getInfoInstance()
                    ->getAdditionalInformation('svea_order_id');
        }
        $order = $payment->getOrder();
        $countryCode = $order->getBillingAddress()->getCountryId();
        $paymentMethodConfig = $this->getSveaStoreConfClass($order->getStoreId());
        $conf = new SveaMageConfigProvider($paymentMethodConfig);
        $sveaObject = WebPay::deliverOrder($conf);
        $response = $sveaObject
                ->setCountryCode($countryCode)
                ->setOrderId($sveaOrderId)
                ->deliverPaymentPlanOrder()
                ->doRequest();

        if ($response->accepted == 1) {
            $successMessage = Mage::helper('svea_webpay')->__('delivered');
            $orderStatus = $this->getConfigData('paid_order_status')
                ?: $order->getStatus();
            if (!empty($orderStatus)) {
                $order->addStatusToHistory($orderStatus, $successMessage, false);
            }
            $rawDetails = $this->_sveaResponseToArray($response);
            $payment->setTransactionId($response->contractNumber)
                ->setIsTransactionClosed(false)
                ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $rawDetails);
//            $order->addStatusToHistory($this->getConfigData('paid_order_status'), $successMessage, false);
//            $payment->setIsTransactionClosed(false);
//            $paymentInfo = $this->getInfoInstance();
//            $paymentInfo->setAdditionalInformation('svea_invoice_id', $response->contractNumber);
//            $order->save();
        } else {
            $errorMessage = $response->errormessage;
            $statusCode = $response->resultcode;
            $errorTranslated = Mage::helper('svea_webpay')->responseCodes($statusCode, $errorMessage);
            $order->addStatusToHistory($order->getStatus(), $errorTranslated, false);
            Mage::throwException($errorTranslated);
        }
    }

    /**
     * End close order request
     *
     * @param type $sveaObject
     * @return type Svea Create order response
     */
    protected function _closeOrder($sveaObject)
    {
        return $sveaObject->closePaymentPlanOrder()
                ->doRequest();
    }

    /**
     * We shouldn't display PaymentPlan as an option if there are no payment
     * plans available
     *
     * @param Mage_Sales_Model_Quote $quote
     */
    public function isAvailable($quote = null)
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }

        $paymentPlans = Mage::helper('svea_webpay')->getPaymentPlanParams($quote);
        return count((array)$paymentPlans) > 0;
    }
}