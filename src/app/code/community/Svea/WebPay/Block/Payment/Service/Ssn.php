<?php

/**
 * SSN field block
 *
 * This block can be placed either together with a payment method or inline
 * together with billing address fields.
 *
 * If placed inline only one instance should be available.
 *
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Block_Payment_Service_Ssn extends Mage_Core_Block_Template
{
    protected $_template = 'svea/payment/service/ssn.phtml';

    /**
     * Check if the template should be rendered or not
     *
     * If $this->getMethod() doesn't return null it will only be rendered if the
     * ssn selector should be displayed in the payment method and vice-versa.
     */
    public function shouldRenderTemplate()
    {
        $helper = Mage::helper('svea_webpay');
        if ($this->getMethod() === null) {
            return !$helper->showSsnSelectorInPaymentMethod();
        } else {
            return $helper->showSsnSelectorInPaymentMethod();
        }
    }

    /**
     * Get the id that will be used in markup css classes and js controller
     *
     * @throws Mage_Exception If the template shouldn't be rendered
     *
     * @returns string|null
     */
    public function getFrontendId()
    {
        if (!$this->shouldRenderTemplate()) {
            throw new Mage_Exception("Template shouldn't be rendered - no instance id can be returned");
        } else {
            if ($method = $this->getMethod()) {
                return $method->getCode();
            } else {
                return 'svea_info';
            }
        }
    }

    public function shouldLockRequiredFields()
    {
        return Mage::helper('svea_webpay')->lockRequiredFields();
    }

}