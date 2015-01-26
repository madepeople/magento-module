<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

class SveaMageConfigProvider implements ConfigurationProvider
{

    private $values;

    public function __construct($values)
    {
        $this->values = $values;
    }

    public function getClientNumber($type, $country)
    {
        return $this->values['client_id'];
    }

    public function getEndPoint($type)
    {
        $type = strtoupper($type);
        switch ($type) {
            case 'HOSTED':
                return $this->values['test'] ? Svea\SveaConfig::SWP_TEST_URL
                    : Svea\SveaConfig::SWP_PROD_URL;
                break;
            case 'INVOICE':
            case 'PAYMENTPLAN':
                return $this->values['test'] ? Svea\SveaConfig::SWP_TEST_WS_URL
                    : Svea\SveaConfig::SWP_PROD_WS_URL;
                break;
            case 'HOSTED_ADMIN':
                return $this->values['test'] ? Svea\SveaConfig::SWP_TEST_HOSTED_ADMIN_URL
                    : Svea\SveaConfig::SWP_PROD_HOSTED_ADMIN_URL;
                break;
            case 'ADMIN':
                return $this->values['test'] ? Svea\SveaConfig::SWP_TEST_ADMIN_URL
                    : Svea\SveaConfig::SWP_PROD_ADMIN_URL;
                break;
            default:
                throw new Exception('Invalid type: ' . $type . ' Accepted values: INVOICE, PAYMENTPLAN, ADMIN, HOSTED, HOSTED_ADMIN.');
        }
    }

    public function getMerchantId($type, $country)
    {
        return $this->values['merchant_id'];
    }

    public function getPassword($type, $country)
    {
        return $this->values['password'];
    }

    public function getSecret($type, $country)
    {
        return $this->values['test'] ? $this->values['secretword_test'] : $this->values['secretword_prod'];
    }

    public function getUsername($type, $country)
    {
        return $this->values['username'];
    }
}
