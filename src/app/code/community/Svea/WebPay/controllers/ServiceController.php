<?php
/**
 * Class to getAddress
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_ServiceController extends Mage_Core_Controller_Front_Action
{

    /** Value that denotes a company in a getAddress response
     *
     * In a response this will be set in the parameter 'type'. It might be a string or integer
     * so when comparing the type parameter should be convert to a string. Doing it the other
     * way is not a good idea in PHP since a lot of strings becomes the integer 0 when
     * converted to integer.
     *
     * @var string
     */
    const TYPE_COMPANY = '1';

    public function getAddressesAction()
    {
        $sveaHelper = Mage::helper('svea_webpay');

        $ssn = $this->getRequest()->getParam('ssn');
        $countryCode = $this->getRequest()->getParam('cc');
        if (empty($countryCode)) {
            $countryCode = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getBillingAddress()
                    ->getCountry();
        }

        $method = $this->getRequest()->getParam('method');
        if (empty($method) || !preg_match('#svea_(invoice|paymentplan)#', $method)) {
            $method = 'svea_invoice';
        }

        // Credentials
        $conf = Mage::getStoreConfig('payment/' . $method);
        $conf['company'] = (string)$this->getRequest()->getParam('type') === self::TYPE_COMPANY;

        try {
            $result = $sveaHelper->getAddresses($ssn, $countryCode, $conf);

            if ($result->resultcode !== 'Error') {
                if (isset($result->customerIdentity) && count($result->customerIdentity)) {
                    $quote = Mage::getSingleton('checkout/session')->getQuote();
                    if ($quote && $quote->getId()) {
                        // Update the billing address so the fetched information is used
                        // in the order object request

                        // The reason for choosing the first identity is that it will be
                        // selected by default in the gui.
                        $identity = $result->customerIdentity[0];
                        $identityParameterMap = array(
                            'firstName' => 'Firstname',
                            'lastName' => 'Lastname',
                            'phoneNumber' => 'Telephone',
                            'zipCode' => 'Postcode',
                            'locality' => 'City',
                            'street' => 'street',
                        );

                        // If this address is a company the 'fullName' attribute should be
                        // stored as a company on the billing address
                        if ((string)$this->getRequest()->getParam('type') === self::TYPE_COMPANY) {
                            $identityParameterMap['fullName'] = 'Company';
                        }


                        $billingAddress = $quote->getBillingAddress();
                        foreach ($identityParameterMap as $source => $target) {
                            if (empty($identity->$source)) {
                                continue;
                            }
                            $method = 'set' . $target;
                            $billingAddress->$method($identity->$source);
                        }

                        $billingAddress->save();

                        // Unsure how sure we can be that this information isn't
                        // overwritten by something else at a later stage, should
                        // we maybe disallow orders without this information on
                        // quote payment? To me it makes sense, OK!
                        $payment = $quote->getPayment();
                        $paymentMethodCode = $payment->getMethod();

                        // Only save getaddress request in the following methods
                        // since it might be used by other methods that also
                        // uses the additional data
                        if (in_array($paymentMethodCode, array('svea_invoice',
                                                               'svea_paymentplan'))) {
                            $additionalData = $payment->getAdditionalData();
                            if (!empty($additionalData)) {
                                $additionalData = unserialize($additionalData);
                            } else {
                                $additionalData = array();
                            }
                            $additionalData['getaddresses_response'] = $result;
                            $payment->setAdditionalData(serialize($additionalData));
                            $payment->save();
                        }
                    }

                }
            } else {
                // resultcode === 'Error'
                if (isset($result->errormessage)) {
                    Mage::log("Got error from svea getAddress: '{$result->errormessage}'");
                } else {
                    Mage::log("Got error from svea getAddress without errormessage");
                }
                $this->getResponse()->setHeader('HTTP/1.0','400',true);
                $result->errormessage = $sveaHelper->__('No customer found');
            }
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
