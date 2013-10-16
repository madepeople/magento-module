<?php

/**
 * @author jonathan@madepeople.se
 */
abstract class Svea_WebPay_Model_Payment_Service_Abstract
    extends Svea_WebPay_Model_Payment_Abstract
{
    protected function _initializeSveaOrder($svea, $order)
    {
        parent::_initializeSveaOrder($svea, $order);
        if (!($svea instanceof Svea\CreateOrderBuilder)) {
            return $this;
        }

        $createdAt = date('Y-m-d', strtotime($order->getCreatedAt()));
        $data = $this->getInfoInstance()->getData($this->getCode());
        $countryCode = $data['country'];
        $customerType = $data['customer_type'];
        $address = $order->getBillingAddress();
        $street = $address->getStreetFull();
        $svea->setClientOrderNumber($order->getIncrementId())
                ->setOrderDate($createdAt)
                ->setCurrency($order->getOrderCurrencyCode());


        // Jesus christ, please, what, how, can we remove this stuff below?
        //
        // Separates the street from the housenumber according to testcases
        $pattern = "/^(?:\s)*([0-9]*[A-ZÄÅÆÖØÜßäåæöøüa-z]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]+)(?:\s*)([0-9]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]*[^\s])?(?:\s)*$/";
        preg_match($pattern, $street, $addressArray);
        if (!array_key_exists(2, $addressArray)) {
            // fix for addresses w/o housenumber
            $addressArray[2] = "";
        }

        if ($customerType === Svea_WebPay_Helper_Data::TYPE_COMPANY) {
            $item = Item::companyCustomer();
            $item->setEmail($address->getEmail())
                    ->setCompanyName($address->getCompany())
                    ->setStreetAddress($addressArray[1], $addressArray[2])
                    ->setZipCode($address->getPostcode())
                    ->setLocality($address->getCity())
                    ->setIpAddress($_SERVER['SERVER_ADDR'])
                    ->setPhoneNumber($address->getTelephone());

            if ($countryCode == "DE" || $countryCode == "NL") {
                Mage::throwException('implement DE and NL');
                $item->setVatNumber($data['vatNo']);
            } else {
                $item->setNationalIdNumber($data['ssn']);
                $item->setAddressSelector($data['addressSelector']);
            }
            $svea->addCustomerDetails($item);
        } else {
            $item = Item::individualCustomer();
            $item->setNationalIdNumber($data['ssn'])
                    ->setEmail($address->getEmail())
                    ->setName($address->getFirstname(), $address->getLastname())
                    ->setStreetAddress($addressArray[1], $addressArray[2])
                    ->setZipCode($address->getPostcode())
                    ->setLocality($address->getCity())
                    ->setIpAddress($_SERVER['SERVER_ADDR']) // This doesn't cut it for reverse proxies
                    ->setPhoneNumber($address->getTelephone());

            if ($countryCode == "DE" || $countryCode == "NL") {
                Mage::throwException('implement DE and NL');
                $item->setBirthDate($data['birthYear'], $data['birthMonth'], $data['birthDay']);
            }
            if ($countryCode == "NL") {
                Mage::throwException('implement DE and NL');
                $item->setInitials($data['initials']);
            }
            $svea->addCustomerDetails($item);
        }

        return $svea;
    }
}