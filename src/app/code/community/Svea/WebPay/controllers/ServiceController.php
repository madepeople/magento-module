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

    public function getAddressesAction()
    {
        $ssn = $this->getRequest()->getParam('ssn');
        $countryCode = $this->getRequest()->getParam('cc');
        if (empty($countryCode)) {
            $countryCode = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getBillingAddress()
                    ->getCountry();
        }

        // Credentials
        $conf = Mage::getStoreConfig('payment/svea_invoice');
        $conf['company'] = $this->getRequest()->getParam('type') == 1;

        try {
            $result = Mage::helper('svea_webpay')->getAddresses($ssn, $countryCode, $conf);

            if (isset($result->customerIdentity)) {
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                if ($quote && $quote->getId()) {
                    // Update the billing address so the fetched information is used
                    // in the order object request
                    $identity = $result->customerIdentity[0];
                    $identityParameterMap = array(
                        'firstName' => 'Firstname',
                        'lastName' => 'Lastname',
                        'phoneNumber' => 'Telephone',
                        'zipCode' => 'Postcode',
                        'locality' => 'City',
                        'street' => 'street',
                    );

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
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
