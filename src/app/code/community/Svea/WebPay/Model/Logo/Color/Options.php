<?php

/**
 * Contains all available logo colors
 *
 */
class Svea_Webpay_Model_Logo_Color_Options extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{

    /**
     * Get all colors
     *
     * @returns array List of options
     */
    public function getAllOptions()
    {
        $helper = Mage::helper('svea_webpay');
        return array(
            array(
                'label' => $helper->__('Color'),
                'value' => 'rgb',
            ),
            array(
                'label' => $helper->__('Black on transparent'),
                'value' => 'bw',
            ),
            array(
                'label' => $helper->__('White on transparent'),
                'value' => 'bw-neg',
            ),
        );
    }

    /**
     * Get all countries as options array
     *
     * @returns array Options array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

}
