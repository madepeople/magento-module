<?php

/**
 * Finish Svea Payment Object for Direct specific values
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Model_Hosted_Direct extends Svea_WebPay_Model_Hosted_Abstract
{

    /**
     * Direct bank orders don't support capture, we consider them captured
     * once they are placed
     *
     * @var bool
     */
    protected $_canCapture = false;
    protected $_canVoid = false;
    protected $_canRefund = true;

    protected $_code = 'svea_directpayment';
    protected $_sveaUrl = 'svea_webpay/hosted/redirect';
    protected $_formBlockType = 'svea_webpay/payment_hosted_direct';

    /**
     * @param type $sveaObject
     * @param type $addressSelector
     * @return DirectPayment
     */
    protected function _choosePayment($sveaObject, $addressSelector = NULL)
    {
        $locale = Mage::app()->getLocale()->getLocaleCode();
        $lang = Mage::helper('svea_webpay')->getLanguageCode($locale);

        $paymentFormPrep = $sveaObject->usePayPageDirectBankOnly()
            ->setPayPageLanguage($lang)
            ->setCallbackUrl(Mage::getUrl('svea_webpay/hosted/callback', array('_secure' => true)))
            ->setReturnUrl(Mage::getUrl('svea_webpay/hosted/return', array('_secure' => true)))
            ->setCancelUrl(Mage::getUrl('svea_webpay/hosted/cancel', array('_secure' => true)));

        return $paymentFormPrep;
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
        $response = $request->queryDirectBankOrder()
            ->doRequest();

        $result = $this->_flatten($response);
        return $result;
    }

}
