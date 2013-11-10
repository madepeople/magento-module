<?php

/**
 * This controller contains various utility functions, so I simply called it...
 * UtilityController
 *
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_UtilityController extends Mage_Core_Controller_Front_Action
{
    /**
     * Fetch the address informtaion from Svea WebPay and set up the billing
     * address in the quote so it will be used if people go back and forth
     * in the checkout process
     *
     * @return void
     */
    public function getAddressAction()
    {
        $ssn = $this->getRequest()->getParam('ssn');
        $method = $this->getRequest()->getParam('method');
        $customerType = $this->getRequest()->getParam('customer_type');
        $country = $this->getRequest()->getParam('country');
        if (empty($ssn) || empty($method) || empty($customerType) || empty($country)) {
            // Just don't do anything
            return;
        }

        try {
            $response = Mage::helper('svea_webpay')->getAddresses(
                    $method,
                    $ssn,
                    $customerType,
                    $country);

            if (isset($response->customerIdentity)) {
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                if ($quote && $quote->getId()) {
                    // Update the billing address so the fetched information is used
                    // in the order object request
                    $identity = $response->customerIdentity[0];
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
                        if (!isset($identity->$source)) {
                            continue;
                        }
                        $setter = 'set' . $target;
                        $billingAddress->$setter($identity->$source);
                    }

                    $billingAddress->save();
                    $response->_billing_address = $billingAddress->toArray();
                }
            }
        } catch (Exception $e) {
            $response = array('errormessage' => $e->getMessage());
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
   }
}