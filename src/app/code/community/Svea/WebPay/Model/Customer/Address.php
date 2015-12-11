<?php

/**
 * Stores customer addresses imported from SVEA
 */
class Svea_Webpay_Model_Customer_Address extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('svea_webpay/customer_address');
    }

    public function setAddress($address)
    {
        $this->_data['address'] = json_encode($address);
    }

    public function getAddress()
    {
        if (!$this->_data['address']) {
            return array();
        } else {
            return json_decode($this->_data['address'], true);
        }
    }

    /**
     * Get this address as it would be returned from a getAddress request
     *
     * @return stdClass
     */
    public function getAsGetAddressResponse()
    {
        $data = $this->getAddress();
        $result = new stdClass();

        $result->firstName = $data['firstName'];
        $result->lastName = $data['lastName'];
        $result->fullName = $data['fullName'];
        $result->addressSelector = $data['addressSelector'];
        $result->customerType = 'Business';
        $result->nationalIdNumber = $data['nationalIdNumber'];
        $result->locality = $data['locality'];
        $result->street = $data['street'];
        $result->zipCode = $data['zipCode'];
        $result->coAddress = $data['coAddress'] . ' testing';

        $result->publicKey = $data['addressSelector'];

        return $result;
    }

}