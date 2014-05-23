<?php

class Svea_WebPay_Model_Source_Payment_Logos
{

    /**
     * Return the options shown in the backend for payment method logos
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => 'VISA',
                'value' => 'visa'
            ),
            array(
                'label' => 'MasterCard',
                'value' => 'mc'
            ),
            array(
                'label' => 'American Express',
                'value' => 'amex'
            ),
        );
    }
}
