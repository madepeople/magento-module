<?php

/**
 * Contains all available company names
 *
 */
class Svea_Webpay_Model_Logo_Companyname_Options extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{

    /**
     * Get all companynames
     *
     * @returns array List of options
     */
    public function getAllOptions()
    {
        $helper = Mage::helper('svea_webpay');

        return array(
            array(
                'label' => 'Svea Ekonomi',
                'value' => 'ekonomi',
            ),
            array(
                'label' => 'Svea Finans',
                'value' => 'finans',
            ),
        );
    }

    /**
     * Get all company names as options array
     *
     * @returns array Options array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

}
