<?php

/**
 * Base Block for payment method markup in the payment method select list
 *
 */
abstract class Svea_WebPay_Block_Payment_Service_Abstract
    extends Svea_WebPay_Block_Payment_Abstract
{

    /**
     * Get the SSN input field/getAddress HTML
     *
     * @return string
     */
    public function getSsnHtml()
    {
        if (!Mage::helper('svea_webpay')->showSsnSelectorInPaymentMethod()) {
            return '';
        } else {
            return $this->getLayout()
                        ->createBlock('svea_webpay/payment_service_ssn')
                        ->setMethod($this->getMethod())
                        ->toHtml();
        }
    }

}