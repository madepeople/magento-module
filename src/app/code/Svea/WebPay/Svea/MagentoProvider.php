<?php

/**
 * Magento Configuration Provider for the Svea WebPay integration package
 *
 * This implementation requires a new instance for every payment method type
 * series of calls. Meaning the same provider instance can't be used for both
 * invoice and hosted information.
 *
 * @see https://github.com/sveawebpay/php-integration
 * @author jonathan@madepeople.se
 */
require_once 'Svea/php-integration/src/Includes.php';
class Svea_WebPay_Svea_MagentoProvider implements ConfigurationProvider
{
    private $_method;

    /**
     * We pass a payment method which is already initialized with store
     * specific information, and it also knows what it is, which is good.
     *
     * @param \Mage_Payment_Model_Method_Abstract $method
     */
    public function __construct(Mage_Payment_Model_Method_Abstract $method)
    {
        $this->_method = $method;
    }

    public function getUsername($type, $country)
    {
        return $this->_method->getConfigData(strtolower($country) . '/username');
    }

    public function getPassword($type, $country)
    {
        return $this->_method->getConfigData(strtolower($country) . '/password');
    }

    public function getClientNumber($type, $country)
    {
        return $this->_method->getConfigData(strtolower($country) . '/client_number');
    }

    public function getMerchantId($type, $country)
    {
        return $this->_method->getConfigData('merchant_id');
    }

    public function getSecret($type, $country)
    {
        return $this->_method->getConfigData('secret');
    }

    public function getEndPoint($type)
    {
        switch (strtoupper($type)) {
            case 'HOSTED':
                return $this->_method->getConfigData('test')
                    ? Svea\SveaConfig::SWP_TEST_URL
                    : Svea\SveaConfig::SWP_PROD_URL;
            case 'INVOICE':
            case 'PAYMENTPLAN':
                return $this->_method->getConfigData('test')
                    ? Svea\SveaConfig::SWP_TEST_WS_URL
                    : Svea\SveaConfig::SWP_PROD_WS_URL;
        }
    }
}