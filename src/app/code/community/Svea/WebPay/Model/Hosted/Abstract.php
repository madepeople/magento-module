<?php

/**
 * Main class for Hosted payments as Card and Direct payment
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
abstract class Svea_WebPay_Model_Hosted_Abstract extends Svea_WebPay_Model_Abstract
{

    protected $_canReviewPayment = true;
    protected $_isGateway = true;

    /**
     * Attempt to accept a payment that is under review
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);
        return true;
    }

    /**
     *
     * @return type url
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl($this->_sveaUrl);
    }

    abstract protected function _choosePayment($sveaObject, $addressSelector = NULL);

    /**
     *
     * @return type Svea Payment form object
     */
    public function getSveaPaymentForm()
    {
        $paymentInfo = $this->getInfoInstance();
        $paymentMethodConfig = $this->getSveaStoreConfClass();

        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $order = $paymentInfo->getOrder();
        } else {
            $order = $paymentInfo->getQuote();
        }

        Mage::helper('svea_webpay')->getPaymentRequest($order, $paymentMethodConfig);
        $sveaRequest = $this->getSveaPaymentObject($order);

        $sveaRequest = $this->_choosePayment($sveaRequest);
        return $sveaRequest;
    }

    /**
     * Validate thru Svea integrationLib only if this is an order
     * @return boolean
     */
    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();

        // If quote, skip validation
        if ($paymentInfo instanceof Mage_Sales_Model_Quote_Payment) {
            return true;
        }

        return parent::validate();
    }

}
