<?php

/**
 * This contains all methods that are shared between the service payment
 * methods, such as initialization of stuff.
 *
 * @author jonathan@madepeople.se
 */
abstract class Svea_WebPay_Model_Payment_Service_Abstract
    extends Svea_WebPay_Model_Payment_Abstract
{

    /**
     * Convert the object returned from Svea to an array because it's easier
     * to handle and store
     *
     * @param object $response
     * @return array
     */
    protected function _sveaResponseToArray($response)
    {
        $result = array();
        foreach ($response as $key => $val) {
            if (!is_string($key) || is_object($val)) {
                continue;
            }
            $result[$key] = $val;
        }
        return $result;
    }

    /**
     * Initialize the Svea order object with the basic information such as
     * address, customer type, SSN, currency etc
     *
     * @param CreateOrderBuilder $svea
     * @param object $order
     * @return \Svea_WebPay_Model_Payment_Service_Abstract|\CreateOrderBuilder
     */
    protected function _initializeSveaOrder($svea, $order)
    {
        parent::_initializeSveaOrder($svea, $order);
        if (!($svea instanceof Svea\CreateOrderBuilder)) {
            return $this;
        }

        $createdAt = date('Y-m-d', strtotime($order->getCreatedAt()));
        $address = $order->getBillingAddress();
        $countryCode = $order->getBillingAddress()->getCountryId();
        $svea->setClientOrderNumber($order->getIncrementId())
                ->setOrderDate($createdAt)
                ->setCurrency($order->getOrderCurrencyCode());

        $data = $this->getInfoInstance()
            ->getData($this->getCode());

        $customerType = $data['customer_type'];
        $typeData = $data[$customerType];

        switch ($customerType) {
            case Svea_WebPay_Helper_Data::TYPE_COMPANY:
                $item = Item::companyCustomer();
                $item->setEmail($address->getEmail())
                    ->setCompanyName($address->getCompany());

                if (in_array($countryCode, array('DE', 'NL'))) {
                    $item->setVatNumber($typeData['ssn_vat']);
                } else {
                    $item->setNationalIdNumber($typeData['ssn_vat']);
                    $item->setAddressSelector($typeData['address_selector']);
                }
                break;
            case Svea_WebPay_Helper_Data::TYPE_INDIVIDUAL:
                $item = Item::individualCustomer();
                $item->setNationalIdNumber($typeData['ssn_vat'])
                    ->setName($address->getFirstname(), $address->getLastname());

                if (in_array($countryCode, array('DE', 'NL'))) {
                    $item->setBirthDate($typeData['birth_year'],
                        $typeData['birth_month'],
                        $typeData['birth_day']);
                }
                if ($countryCode === 'NL') {
                    $item->setInitials($typeData['initials']);
                }
                break;
        }

        $item->setEmail($address->getEmail())
            ->setStreetAddress($address->getStreetFull(), $typeData['housenumber'])
            ->setZipCode($address->getPostcode())
            ->setLocality($address->getCity())
            ->setIpAddress(Mage::helper('core/http')->getRemoteAddr(false))
            ->setPhoneNumber($address->getTelephone());

        $svea->addCustomerDetails($item);

        return $svea;
    }
}