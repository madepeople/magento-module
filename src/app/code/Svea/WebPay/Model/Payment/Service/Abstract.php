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
        $data = $this->getInfoInstance()->getData($this->getCode());
        $address = $order->getBillingAddress();
        $street = $address->getStreetFull();
        $countryCode = $order->getBillingAddress()->getCountryId();
        $customerType = $data['customer_type'];
        $svea->setClientOrderNumber($order->getIncrementId())
                ->setOrderDate($createdAt)
                ->setCurrency($order->getOrderCurrencyCode());


        // Jesus christ, please, what, how, can we remove this stuff below?
        //
        // We could possible use javascript to dynamically insert a house number
        // field when the country selector has NL selected. We need to switch
        // other personal number/address things depending on country anyway
        //
        // Separates the street from the housenumber according to testcases
        $pattern = "/^(?:\s)*([0-9]*[A-ZÄÅÆÖØÜßäåæöøüa-z]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]+)(?:\s*)([0-9]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]*[^\s])?(?:\s)*$/";
        preg_match($pattern, $street, $addressArray);
        if (!array_key_exists(2, $addressArray)) {
            // fix for addresses w/o housenumber
            $addressArray[2] = "";
        }

        $typeData = $data[$customerType];
        if ($customerType === Svea_WebPay_Helper_Data::TYPE_COMPANY) {
            $item = Item::companyCustomer();
            $item->setEmail($address->getEmail())
                    ->setCompanyName($address->getCompany())
                    ->setStreetAddress($addressArray[1], $addressArray[2])
                    ->setZipCode($address->getPostcode())
                    ->setLocality($address->getCity())
                    ->setIpAddress(Mage::helper('core/http')->getRemoteAddr(false)) // Not good enough for reverse proxies
                    ->setPhoneNumber($address->getTelephone());

            if (in_array($countryCode, array('DE', 'NL'))) {
                $item->setVatNumber($typeData['vat']);
            } else {
                $item->setNationalIdNumber($typeData['ssn']);
                $item->setAddressSelector($typeData['addressSelector']);
            }
            $svea->addCustomerDetails($item);
        } else {
            $item = Item::individualCustomer();
            $item->setNationalIdNumber($typeData['ssn'])
                    ->setEmail($address->getEmail())
                    ->setName($address->getFirstname(), $address->getLastname())
                    ->setStreetAddress($addressArray[1], $addressArray[2])
                    ->setZipCode($address->getPostcode())
                    ->setLocality($address->getCity())
                    ->setIpAddress(Mage::helper('core/http')->getRemoteAddr(false))
                    ->setPhoneNumber($address->getTelephone());

            if (in_array($countryCode, array('DE', 'NL'))) {
                $item->setBirthDate($typeData['birthYear'], $typeData['birthMonth'], $typeData['birthDay']);
            }
            if ($countryCode === 'NL') {
                $item->setInitials($typeData['initials']);
            }
            $svea->addCustomerDetails($item);
        }

        return $svea;
    }
}