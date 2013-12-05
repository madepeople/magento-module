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