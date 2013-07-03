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
        $company = isset($additionalInfo['svea_customerType']) ? $additionalInfo['svea_customerType'] : FALSE;
        $address = $order->getBillingAddress()->getStreetFull();

        preg_match('!( [^0-9]*)(.*)!', $address, $houseNoArr);
        $houseNo = $houseNoArr[2];

        preg_match('((.*)([^0-9])(.[^0-9]))', $address, $streetArr);
        $street = $streetArr[0];

        if ($company) {
            $item = Item::companyCustomer();

            $item = $item->setEmail($order->getBillingAddress()->getEmail())
                    ->setCompanyName($order->getBillingAddress()->getCompany())
                    ->setStreetAddress($street, $houseNo)
                    ->setZipCode($order->getBillingAddress()->getPostcode())
                    ->setLocality($order->getBillingAddress()->getCity())
                    ->setIpAddress($_SERVER['SERVER_ADDR'])
                    ->setPhoneNumber($order->getBillingAddress()->getTelephone());

            if ($countryCode == "DE" || $countryCode == "NL") {

                $item = $item->setVatNumber($additionalInfo['svea_vatNo']);
            } else {
                $item = $item->setNationalIdNumber($additionalInfo['svea_ssn']);
                $item = $item->setAddressSelector($additionalInfo['addressSelector']);
            }
            $svea = $svea->addCustomerDetails($item);
        } else {
            $item = Item::individualCustomer();
            $item = $item->setNationalIdNumber($additionalInfo['svea_ssn'])
                    ->setEmail($order->getBillingAddress()->getEmail())
                    ->setName($order->getBillingAddress()->getFirstname(), $order->getBillingAddress()->getLastname())
                    ->setStreetAddress($street, $houseNo)
                    ->setZipCode($order->getBillingAddress()->getPostcode())
                    ->setLocality($order->getBillingAddress()->getCity())
                    ->setIpAddress($_SERVER['SERVER_ADDR'])//try getRemoteIp()
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
            $order->save();
        } else {
            $errorMessage = $response->errormessage;
            $statusCode = $response->resultcode;
            $errorTranslated = Mage::helper('svea_webpay')->responseCodes($statusCode, $errorMessage);
            if ($order->canCancel()) {
                $order->addStatusToHistory($order->getStatus(), $errorTranslated, false);
                $order->cancel();
                $order->save();
            }

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
        $auth = $this->getSveaStoreConfClass();
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