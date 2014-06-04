<?php

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
        if (Mage::helper('svea_webpay')->usingQuickCheckout()) {
            // When we use a quick checkout type, we usually fetch the SSN
            // thing elsewhere and put it with the billing address
            return '';
        }

        return $this->getLayout()
            ->createBlock('svea_webpay/payment_service_ssn')
            ->setMethod($this->getMethod())
            ->toHtml();
    }
}