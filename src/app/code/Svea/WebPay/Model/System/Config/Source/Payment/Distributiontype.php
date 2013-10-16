<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_System_Config_Source_Payment_Distributiontype
{
    /**
     * The merchant can choose whether or not to send the invoice with normal
     * post, or an e-mail
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => 'Post',
                'value' => 'POST'
            ),
            array(
                'label' => 'E-mail',
                'value' => 'EMAIL',
            )
        );
    }

}