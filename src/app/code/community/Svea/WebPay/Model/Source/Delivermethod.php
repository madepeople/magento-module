<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

class Svea_WebPay_Model_Source_Delivermethod
{

    /**
     * Return the options shown in the backend for method status
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => 'Post',
                'value' => DistributionType::POST
            ),
            array(
                'label' => 'Email',
                'value' => DistributionType::EMAIL,
            )
        );
    }
}
