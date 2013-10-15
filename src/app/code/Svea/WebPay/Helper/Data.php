<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Helper_Data extends Mage_Core_Helper_Abstract
{
    const TYPE_COMPANY = 'company';
    const TYPE_INDIVIDUAL = 'individual';

    public function getAddresses($method, $ssn, $customerType, $country = null)
    {
        if ($country === null) {
            $store = Mage::app()->getStore();
            $country = $this->getMerchantCountry($store);
        }

        $methodInstance = Mage::getModel('svea_webpay/payment_service_' . $method);
        $sveaConfig = new Svea_WebPay_Svea_MagentoProvider($methodInstance);

        $addressRequest = WebPay::getAddresses($sveaConfig)
                ->setOrderTypeInvoice()
                ->setCountryCode($country);

        switch ($customerType) {
            case self::TYPE_INDIVIDUAL:
                $addressRequest->setIndividual($ssn);
                break;
            case self::TYPE_COMPANY:
                $addressRequest->setCompany($ssn);
                break;
        }

        return $addressRequest->doRequest();
    }

    public function getMerchantCountry($store = null)
    {
        $country = Mage::getStoreConfig('general/store_information/merchant_country', $store);
        if (empty($country)) {
            $country = Mage::getStoreConfig('general/country/default', $store);
        }
        return $country;
    }
}