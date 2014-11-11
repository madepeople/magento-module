<?php

/**
 * Contains all countries where a paymentplan contract can be signed with SVEA.
 *
 * This is used as source for the admin setting 'client_country' for
 * Svea Paymentplan.
 */
class Svea_Webpay_Model_Paymentplan_Client_Country_Options extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{

    /**
     * List of valid country codes
     */
    private $_countryCodes = array(
        'SE',
        'NO',
        'DK',
        'FI',
    );

    /**
     * List of countries as a magento options array
     *
     * Is populated in getAllOptions().
     *
     * @var array
     */
    private $_countries = null;

    /**
     * Get all countries
     *
     * @returns array List of options
     */
    public function getAllOptions()
    {
        if ($this->_countries === null) {
            $helper = Mage::getModel('directory/country');
            foreach ($this->_countryCodes as $countryCode) {
                $this->_countries[] = array(
                    'label' => $helper->loadByCode($countryCode)->getName(),
                    'value' => $countryCode,
                );
            }
        }

        return $this->_countries;
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
