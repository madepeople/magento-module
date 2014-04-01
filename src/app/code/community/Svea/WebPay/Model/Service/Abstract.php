<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

/**
 * Main class for WebService payments as Invoice and PaymentPlan payment
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
abstract class Svea_WebPay_Model_Service_Abstract extends Svea_WebPay_Model_Abstract
{
    protected $_isGateway = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canVoid = true;

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
     * Verify that the address posted is the one chosen from the getaddresses
     * response. Also set the billing/shipping addresses to the ones returned
     * from svea.
     *
     * @param $payment
     * @return $this
     * @throws Mage_Payment_Exception
     */
    protected function _setAddressToSveaAddress($payment)
    {
        // We need to verify that we have address data to work with, and we also
        // have to verify the address key in the address data with the posted
        // one from the ordering process
        $sveaInformation = $payment->getAdditionalInformation();

        $order = $payment->getOrder();
        if (!empty($sveaInformation) && !empty($sveaInformation['svea_addressSelector'])) {
            // Get address has been used, and we need to override the billing
            // address that the customer has entered, and possibly also the
            // shipping address
            $quote = $order->getQuote();
            $additionalData = unserialize($quote->getPayment()->getAdditionalData());
            if (empty($additionalData) || empty($additionalData['getaddresses_response'])) {
                throw new Mage_Payment_Exception("Can't fetch address information for order. Please contact support.");
            }

            $addressData = $additionalData['getaddresses_response'];
            $address = null;
            foreach ($addressData->customerIdentity as $identity) {
                if ($identity->addressSelector == $sveaInformation['svea_addressSelector']) {
                    $address = $identity;
                    break;
                }
            }

            if (null === $address) {
                throw new Mage_Payment_Exception('Selected civil registry address does not match the database.');
            }

            // Set the order addresses to the civil registry information
            foreach ($order->getAddressesCollection() as $orderAddress) {
                $orderAddress->setFirstname($address->firstName)
                    ->setLastname($address->lastName . ' ' . $address->coAddress)
                    ->setCity($address->locality)
                    ->setPostcode($address->zipCode)
                    ->setStreet($address->street);
            }
        } else if (in_array($order->getCountryId(), array('SE', 'DK'))) {
            $message = Mage::helper('svea_webpay')->__('Please click the "Get Address" button to fetch your address information and proceed.');
            throw new Mage_Payment_Exception($message);
        }

        return $this;
    }

    /**
     * Add Customer details to Svea CreateOrder object
     *
     * @param type $order
     * @param type $additionalInfo
     * @return type Svea CreateOrder object
     */
    public function getSveaPaymentObject($order, $additionalInfo = null)
    {
        $svea = parent::getSveaPaymentObject($order, $additionalInfo);
        //Add more customer info
        $countryCode = $order->getBillingAddress()->getCountryId();
        $company = $additionalInfo['svea_customerType'];
        $address = $order->getBillingAddress()->getStreetFull();

        //Seperates the street from the housenumber according to testcases
        $pattern = "/^(?:\s)*([0-9]*[A-ZÄÅÆÖØÜßäåæöøüa-z]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]+)(?:\s*)([0-9]*\s*[A-ZÄÅÆÖØÜßäåæöøüa-z]*[^\s])?(?:\s)*$/";
        preg_match($pattern, $address, $addressArray);
        if( !array_key_exists( 2, $addressArray ) ) { $addressArray[2] = ""; } //fix for addresses w/o housenumber

        if ($company == "1") {
            $item = Item::companyCustomer();
            $item = $item->setEmail($order->getBillingAddress()->getEmail())
                    ->setCompanyName($order->getBillingAddress()->getCompany())
                    ->setStreetAddress($addressArray[1], $addressArray[2])
                    ->setZipCode($order->getBillingAddress()->getPostcode())
                    ->setLocality($order->getBillingAddress()->getCity())
                    ->setIpAddress($_SERVER['SERVER_ADDR'])
                    ->setPhoneNumber($order->getBillingAddress()->getTelephone());

            if ($countryCode == "DE" || $countryCode == "NL") {
                $item = $item->setVatNumber($additionalInfo['svea_vatNo']);
            } else {
                $item = $item->setNationalIdNumber($additionalInfo['svea_ssn']);
                $item = $item->setAddressSelector($additionalInfo['svea_addressSelector']);
            }
            $svea = $svea->addCustomerDetails($item);
        } else {
            $item = Item::individualCustomer();
            $item = $item->setNationalIdNumber($additionalInfo['svea_ssn'])
                    ->setEmail($order->getBillingAddress()->getEmail())
                    ->setName($order->getBillingAddress()->getFirstname(), $order->getBillingAddress()->getLastname())
                    ->setStreetAddress($addressArray[1], $addressArray[2])
                    ->setZipCode($order->getBillingAddress()->getPostcode())
                    ->setLocality($order->getBillingAddress()->getCity())
                    ->setIpAddress($_SERVER['SERVER_ADDR']) // This doesn't cut it for reverse proxies
                    ->setPhoneNumber($order->getBillingAddress()->getTelephone());

            if ($countryCode == "DE" || $countryCode == "NL") {
                $item = $item->setBirthDate($additionalInfo['svea_birthYear'], $additionalInfo['svea_birthMonth'], $additionalInfo['svea_birthDay']);
            }
            if ($countryCode == "NL") {
                $item = $item->setInitials($additionalInfo['svea_initials']);
            }
            $svea = $svea->addCustomerDetails($item);
        }
        return $svea;
    }

    /**
     * Svea Create Order call to API
     *
     * @param Varien_Object $payment
     * @param type $amount
     * @return type
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $order = $payment->getOrder();
        // Object created in validate()
        $sveaObject = $order->getData('svea_payment_request');
        $sveaObject = $this->_choosePayment($sveaObject);
        $this->_setAddressToSveaAddress($payment);

        $response = $sveaObject->doRequest();
        if ($response->accepted == 1) {
            $successMessage = Mage::helper('svea_webpay')->__('authorize_success %s', $response->sveaOrderId);
            $order->addStatusToHistory($this->getConfigData('paid_order_status'), $successMessage, false);
            $paymentInfo = $this->getInfoInstance();
            $paymentInfo->setAdditionalInformation('svea_order_id', $response->sveaOrderId);

            $rawDetails = array();
            foreach ($response as $key => $val) {
                if (!is_string($key) || is_object($val)) {
                    continue;
                }
                $rawDetails[$key] = $val;
            }
            $payment->setTransactionId($response->sveaOrderId)
                    ->setIsTransactionClosed(false)
                    ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $rawDetails);
        } else {
            $errorMessage = $response->errormessage;
            $statusCode = $response->resultcode;
            $errorTranslated = Mage::helper('svea_webpay')->responseCodes($statusCode, $errorMessage);
            $order->addStatusToHistory($order->getStatus(), $errorTranslated, false);

            return Mage::throwException($errorTranslated);
        }
    }

    /**
     * For Svea, it's the same as void
     *
     * @param Varien_Object $payment
     */
    public function cancel(Varien_Object $payment)
    {
        $this->void($payment);
    }

    /**
     * Svea Close Order
     *
     * @param Varien_Object $payment
     * @return type
     */
    public function void(Varien_Object $payment)
    {
        $order = $payment->getOrder();
        $auth = $this->getSveaStoreConfClass($order->getStoreId());
        $conf = new SveaMageConfigProvider($auth);

        $sveaObject = WebPay::closeOrder($conf);
        $sveaObject->setOrderId($payment->getTransactionId())
                ->setCountryCode("");

        $response = $this->_closeOrder($sveaObject);
        if ($response->accepted == 1) {
            return parent::void($payment);
        } else {
            $errorMessage = $response->errormessage;
            $statusCode = $response->resultcode;
            $errorTranslated = Mage::helper('svea_webpay')->responseCodes($statusCode, $errorMessage);

            Mage::throwException($errorTranslated);
        }
    }
}
