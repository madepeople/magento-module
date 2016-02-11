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
        // We don't do getAddress() requests for Finland
        $billingCountryId = $order->getBillingAddress()->getCountryId();
        if ($billingCountryId === 'FI') {
            return;
        }
        if (!empty($sveaInformation) && !empty($sveaInformation['svea_addressSelector'])) {
            // Get address has been used, and we need to override the billing
            // address that the customer has entered, and possibly also the
            // shipping address
            $quote = $order->getQuote();
            if (empty($quote)) {
                // This is a magento 1.4 thing, the order has the quote_id
                $quote = Mage::getModel('sales/quote')
                    ->load($order->getQuoteId());
            }
            if (empty($quote)) {
                Mage::throwException('Could not load the quote associated with the order');
            }
            $address = null;
            // Huh?
            $additionalData = unserialize($quote->getPayment()->getAdditionalData());
            if (empty($additionalData) || empty($additionalData['getaddresses_response'])) {
                // The getAddress button might not have been clicked, issue a
                // new getAddress call and use the first address if only one is
                // returned
                $conf = Mage::getStoreConfig('payment/' . $this->getCode());

                // Select company from $_POST
                // NOPUSH: TODO: Explain why this happens and why setting
                // company to false is ok.

                $conf['company'] = false;
                foreach (array($payment->getMethod(), 'svea_info') as $formKey) {
                    if (array_key_exists($formKey, $_POST['payment'])) {
                        $conf['company'] = (int)$sveaInformation['svea_customerType'] === 1;
                    }
                }

                $result = Mage::helper('svea_webpay')->getAddresses(
                    $sveaInformation['svea_ssn'],
                    $order->getBillingAddress()->getCountryId(),
                    $conf);

                if (!isset($result->customerIdentity)) {
                    throw new Mage_Payment_Exception("Can't fetch address information for order. Please contact support.");
                }

                foreach ($result->customerIdentity as $identity) {
                    if ($identity->addressSelector === $sveaInformation['svea_addressSelector']) {
                        $address = $identity;
                        break;
                    }
                }
            } else {
                $addressData = $additionalData['getaddresses_response'];
                foreach ($addressData->customerIdentity as $identity) {
                    if ($identity->addressSelector == $sveaInformation['svea_addressSelector']) {
                        $address = $identity;
                        break;
                    }
                }
            }

            if (null === $address) {
                throw new Mage_Payment_Exception('Selected civil registry address does not match the database.');
            }

            $allowCustomShippingAddress = Mage::helper('svea_webpay')->allowCustomShippingAddress();

            // Set the order addresses to the civil registry information
            foreach ($order->getAddressesCollection() as $orderAddress) {
                if ($orderAddress->getAddressType() === 'shipping' && $allowCustomShippingAddress) {
                    continue;
                }
                if ($sveaInformation['svea_customerType'] == 0) {
                    // Don't overwrite the name if a company
                    $orderAddress->setFirstname($address->firstName)
                                 ->setLastname($address->lastName . ' ' . $address->coAddress);
                }
                $orderAddress->setCity($address->locality)
                             ->setPostcode($address->zipCode)
                             ->setStreet($address->street);
            }
        } else if (in_array($order->getBillingAddress()->getCountryId(), array('SE', 'DK'))) {
            $message = Mage::helper('svea_webpay')->__('Please click the "Get Address" button to fetch your address information and proceed.');
            Mage::throwException($message);
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

        $addressArray = \Svea\Helper::splitStreetAddress($address);

        if ($company == "1") {
            $item = WebPayItem::companyCustomer();
            $item = $item->setEmail($order->getBillingAddress()->getEmail())
                    ->setCompanyName($order->getBillingAddress()->getCompany())
                    ->setStreetAddress($addressArray[1], $addressArray[2])
                    ->setZipCode($order->getBillingAddress()->getPostcode())
                    ->setLocality($order->getBillingAddress()->getCity())
                    ->setIpAddress(Mage::helper('core/http')->getRemoteAddr(false))
                    ->setPhoneNumber($order->getBillingAddress()->getTelephone());

            if ($countryCode == "DE" || $countryCode == "NL") {
                $item = $item->setVatNumber($additionalInfo['svea_vatNo']);
            } else {
                $item = $item->setNationalIdNumber($additionalInfo['svea_ssn']);
                $item = $item->setAddressSelector($additionalInfo['svea_addressSelector']);
            }
        } else {
            $item = WebPayItem::individualCustomer();

            // Not all countries has svea_ssn input
            if (array_key_exists('svea_ssn', $additionalInfo)) {
                $item = $item->setNationalIdNumber($additionalInfo['svea_ssn']);
            }

            $item = $item->setEmail($order->getBillingAddress()->getEmail())
                    ->setName($order->getBillingAddress()->getFirstname(), $order->getBillingAddress()->getLastname())
                    ->setStreetAddress($addressArray[1], $addressArray[2])
                    ->setZipCode($order->getBillingAddress()->getPostcode())
                    ->setLocality($order->getBillingAddress()->getCity())
                    ->setIpAddress(Mage::helper('core/http')->getRemoteAddr(false))
                    ->setPhoneNumber($order->getBillingAddress()->getTelephone());

            if ($countryCode == "DE" || $countryCode == "NL") {
                $validBirthday = true;
                foreach (array(
                    'svea_birthYear',
                    'svea_birthMonth',
                    'svea_birthDay',
                ) as $key) {
                    if (!array_key_exists($key, $additionalInfo) || trim($additionalInfo[$key]) === "") {
                        $validBirthday = false;
                    }
                }

                if ($validBirthday) {
                    $item = $item->setBirthDate($additionalInfo['svea_birthYear'], $additionalInfo['svea_birthMonth'], $additionalInfo['svea_birthDay']);
                }
            }
            if ($countryCode == "NL") {
                if (array_key_exists('svea_initials', $additionalInfo)) {
                    $item = $item->setInitials($additionalInfo['svea_initials']);
                }
            }

        }
        // Set public key on the object if publicKey is set in additionalInfo
        if (array_key_exists('svea_publicKey', $additionalInfo)) {
            $publicKey = $additionalInfo['svea_publicKey'];
            if ($publicKey !== '') {
                $item = $item->setPublicKey($publicKey);
                // $item = $item->setAddressSelector('');
            }
        }
        $svea = $svea->addCustomerDetails($item);
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

        $paymentInfo = $this->getInfoInstance();
        $additionalInfo = $paymentInfo->getAdditionalInformation();

        // Add _POST data to additionalInfo if it's not already set in payment
        // This is because some countries like Finland doesn't make a getAddress()
        // call so for onepage checkouts the svea_ info has to be taken from _POST
        // _but_ for multi-page checkouts that information has already been posted
        $postSveaInfo = @$_POST['payment'][@$_POST['payment']['method']];
        if (is_array($postSveaInfo)) {
            foreach ($postSveaInfo as $key => $value) {
                if (strpos($key, 'svea_') === 0) {
                    if (!array_key_exists($key, $additionalInfo)) {
                        $additionalInfo[$key] = $value;
                    }
                }
            }
        }
        $paymentInfo->setAdditionalInformation($additionalInfo);
        // Save the information in database
        $resource = Mage::getSingleton('core/resource');
        $tableName = $resource->getTableName('sales_flat_quote_payment');
        $connection = $resource->getConnection('core_write');
        $connection->query("UPDATE {$tableName} SET additional_information=:data WHERE payment_id=:paymentId LIMIT 1",
                           array(
                               'data' => serialize($additionalInfo),
                               'paymentId' => $paymentInfo->getId(),
                           ));

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

            $this->_replaceBillingAddress($order, $response);

            $payment->setTransactionId($response->sveaOrderId)
                    ->setIsTransactionClosed(false)
                    ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $rawDetails);

        } else {
            $errorMessage = $response->errormessage;
            $statusCode = $response->resultcode;
            $errorTranslated = Mage::helper('svea_webpay')->responseCodes($statusCode, $errorMessage);
            $order->addStatusToHistory($order->getStatus(), $errorTranslated, false);

            Mage::throwException($errorTranslated);
        }
    }

    /**
     * Replace all order addresses with the address returned in a svea response
     *
     * @param $order The order
     * @param $response A Svea createOrder response
     */
    private function _replaceBillingAddress($order, $response)
    {
        // Set billing address for FI customers
        // getAddress() is not called for FI customers according to SVEA
        // specifications so the billing address should be overwritten
        // with the response address even though the customer is not
        // notified about that.
        $quote = $order->getQuote();
        $quoteBillingAddress = $quote->getBillingAddress();
        $addresses = array(
            $order->getBillingAddress(),
            $quoteBillingAddress,
        );

        if (!Mage::helper('svea_webpay')->allowCustomShippingAddress()) {
            $addresses[] = $order->getShippingAddress();
            $addresses[] = $quote->getShippingAddress();
        }

        if (Mage::helper('svea_webpay')->createOrderOverwritesAddressForCountry($quoteBillingAddress->getCountryId())) {
            $identity = $response->customerIdentity;
            $identityParameterMap = array(
                'firstName' => 'Firstname',
                'lastName' => 'Lastname',
                'phoneNumber' => 'Telephone',
                'zipCode' => 'Postcode',
                'locality' => 'City',
                'street' => 'Street',
            );

            if ($identity->customerType === 'Company') {
                $identityParameterMap['fullName'] = 'Company';
            }

            foreach ($identityParameterMap as $source => $target) {
                if (!isset($identity->$source)) {
                    continue;
                }
                $method = "set{$target}";
                foreach ($addresses as $address) {
                    $address->$method($identity->$source);
                }
            }

            // The response for individual only has fullName set -
            // explode on comma and set firstName/lastName
            if ($identity->customerType === 'Individual') {
                list($lastName, $firstName) = explode(',', $identity->fullName);
                foreach ($addresses as $address) {
                    $address->setFirstname($firstName);
                    $address->setLastname($lastName);
                }
            }
            foreach ($addresses as $address) {
                $address->save();
            }
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
