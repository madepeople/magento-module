<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

class SveaMageConfigProvider implements ConfigurationProvider{

    private $values;


    public function __construct($values) {
        $this->values = $values;
    }

    public function getClientNumber($type, $country) {
        return $this->values['client_id'];
    }

    public function getEndPoint($type) {
        $type = strtoupper($type);
        if($type == "HOSTED"){
            return $this->values['test'] ? Svea\SveaConfig::SWP_TEST_URL : Svea\SveaConfig::SWP_PROD_URL;
        }elseif($type == "INVOICE" || $type == "PAYMENTPLAN"){
            return $this->values['test'] ? Svea\SveaConfig::SWP_TEST_WS_URL: Svea\SveaConfig::SWP_PROD_WS_URL;
        }  else {
           throw new Exception('Invalid type: '.$type.' Accepted values: INVOICE, PAYMENTPLAN or HOSTED.');
        }
    }

    public function getMerchantId($type, $country) {
        return $this->values['merchant_id'];
    }

    public function getPassword($type, $country) {
        return $this->values['password'];
    }

    public function getSecret($type, $country) {
        return $this->values['test'] ? $this->values['secretword_test'] : $this->values['secretword_prod'];
    }

    public function getUsername($type, $country) {
        return $this->values['username'];
    }
}
