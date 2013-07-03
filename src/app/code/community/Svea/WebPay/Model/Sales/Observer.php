<?php
/**
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Model_Sales_Observer
{
    public function addPaymentFeeToPayment(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment();
        if (!preg_match('/svea_invoice/', $payment->getMethod())) {
            return;
        }

        $paymentFee = $order->getBillingAddress()->getPaymentFee();
        if (empty($paymentFee)) {
            return;
        }

        $payment->setAdditionalInformation('svea_payment_fee', $paymentFee)
                ->save();
    }

    public function autodeliverOrder(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();

        if ($payment->getMethodInstance()->getConfigData('autodeliver')) {
            $payment->capture(null);
        }
    }

    public function setupHostedOrder(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();

        if (preg_match('/^svea_(card|direct)payment$/', $payment->getMethod())) {
            $payment->setIsTransactionPending(true);
        }
    }
}