<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_Payment_Hosted
    extends Svea_WebPay_Model_Payment_Abstract
{
    /**
     * Retrieve payment method code. The reason we implement this is to use
     * the same model for both card and direct payments as they share the same
     * flow with just some settings that differ
     *
     * @return string
     */
    public function getCode()
    {
        Mage::throwException('Implement me');
    }
}