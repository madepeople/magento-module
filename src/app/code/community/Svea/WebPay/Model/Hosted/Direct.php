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

    protected $_code = 'svea_directpayment';
    protected $_sveaUrl = 'svea_webpay/hosted/redirectdirect';
    protected $_formBlockType = 'svea_webpay/payment_hosted_direct';

    /**
     * @param type $sveaObject
     * @param type $addressSelector
     * @return DirectPayment
     */
    protected function _choosePayment($sveaObject, $addressSelector = NULL)
    {
        $paymentFormPrep = $sveaObject->usePayPageDirectBankOnly()
                ->setPayPageLanguage(Mage::helper('svea_webpay')->__('lang_code'))
                ->setReturnUrl(Mage::getUrl('svea_webpay/hosted/responseDirectPayment'))
                ->setCancelUrl(Mage::getUrl('checkout/cart/'));

        return $paymentFormPrep;
    }

}
