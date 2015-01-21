<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

/**
 * Create hidden form to send by POST, when paying by credit card
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Block_Payment_Hosted_Redirect extends Mage_Core_Block_Template
{

    protected $_template = 'svea/payment/hosted/redirect.phtml';

    public function getFormHtml()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $order = Mage::getModel('sales/order')->load($orderId);

        $payment = $order->getPayment()->getMethodInstance();

        $sveaObject = $payment->getSveaPaymentForm();
        $paymentForm = $sveaObject->getPaymentForm();

        return $paymentForm->htmlFormFieldsAsArray;
    }

}
