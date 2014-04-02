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

        // We need to verify that we have address data to work with, and we also
        // have to verify the address key in the address data with the posted
        // one from the ordering process
        $sveaInformation = $this->getInfoInstance()
            ->getAdditionalInformation($this->getCode());

        if (!empty($sveaInformation) && !empty($sveaInformation['address_selector'])) {
            // Get address has been used, and we need to override the billing
            // address that the customer has entered, and possibly also the
            // shipping address
            $quote = $order->getQuote();
            $additionalData = unserialize($quote->getPayment()->getAdditionalData());
            if (empty($additionalData) || empty($additionalData['getaddresses_response'])) {
                Mage::throwException("Can't fetch address information for order. Please contact support.");
            }

            $addressData = $additionalData['getaddresses_response'];
            $address = null;
            foreach ($addressData->customerIdentity as $identity) {
                if ($identity->addressSelector == $sveaInformation['address_selector']) {
                    $address = $identity;
                    break;
                }
            }

            if (null === $address) {
                Mage::throwException('Selected civil registry address does not match the database.');
            }

            // Set the order addresses to the civil registry information
            foreach ($order->getAddressesCollection() as $orderAddress) {
                $orderAddress->setFirstname($address->firstName)
                    ->setLastname($address->lastName . ' ' . $address->coAddress)
                    ->setCity($address->locality)
                    ->setPostcode($address->zipCode)
                    ->setStreet($address->street);
            }
        } else if (in_array($order->getBillingAddress()->getCountryId(), array('SE', 'DK'))) {
            $message = Mage::helper('svea_webpay')->__('Please click the "Get Address" button to fetch your address information and proceed.');
            Mage::throwException($message);
        }

        $billingAddress = $order->getBillingAddress();
        $countryCode = $billingAddress->getCountryId();

        $data = $this->getInfoInstance()
            ->getData($this->getCode());

        $customerType = $data['customer_type'];
        $typeData = $data[$customerType];

        switch ($customerType) {
            case Svea_WebPay_Helper_Data::TYPE_COMPANY:
                $item = Item::companyCustomer();
                $item->setCompanyName($billingAddress->getCompany());

                switch ($countryCode) {
                    case 'SE':
                    case 'NO':
                    case 'DK':
                    case 'FI':
                        $item->setNationalIdNumber($typeData['ssn_vat']);
                        $item->setAddressSelector($typeData['address_selector']);
                        break;
                    case 'NL':
                    case 'DE':
                        $item->setVatNumber($typeData['ssn_vat']);
                        break;
                }
                break;
            case Svea_WebPay_Helper_Data::TYPE_INDIVIDUAL:
                $item = Item::individualCustomer();
                $item->setNationalIdNumber($typeData['ssn_vat'])
                    ->setName($billingAddress->getFirstname(), $billingAddress->getLastname());

                switch ($countryCode) {
                    case 'NL':
                        $item->setInitials($typeData['initials']);
                    case 'DE':
                        $item->setBirthDate($typeData['birth_year'],
                            $typeData['birth_month'],
                            $typeData['birth_day']);
                        break;
                }
                break;
        }

        $item->setEmail($billingAddress->getEmail())
            ->setStreetAddress($billingAddress->getStreetFull(), $typeData['housenumber'])
            ->setZipCode($billingAddress->getPostcode())
            ->setLocality($billingAddress->getCity())
            ->setIpAddress(Mage::helper('core/http')->getRemoteAddr(false))
            ->setPhoneNumber($billingAddress->getTelephone());

        $svea->setClientOrderNumber($order->getIncrementId())
            ->setOrderDate(date('Y-m-d', strtotime($order->getCreatedAt())))
            ->setCurrency($order->getOrderCurrencyCode())
            ->addCustomerDetails($item);

        return $svea;
    }
}