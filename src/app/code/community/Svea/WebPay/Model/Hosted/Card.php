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

}