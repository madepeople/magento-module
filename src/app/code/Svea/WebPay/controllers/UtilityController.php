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
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $country = $quote->getBillingAddress()->getCountryId();
        $ssnVat = $this->getRequest()->getParam('ssn_vat');
        $method = $this->getRequest()->getParam('method');
        $customerType = $this->getRequest()->getParam('customer_type');
        if (empty($ssnVat) || empty($method) || empty($customerType) || empty($country)) {
            // Just don't do anything
            return;
        }

        try {
            $response = Mage::helper('svea_webpay')->getAddresses(
                $method,
                $ssnVat,
                $customerType,
                $country);

            if (isset($response->customerIdentity)) {
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
                    $response->address_html = $this->getLayout()
                        ->createBlock('svea_webpay/payment_service_address')
                        ->setAddress($billingAddress)
                        ->toHtml();
                }
            }
        } catch (Exception $e) {
            $response = array('errormessage' => $e->getMessage());
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
   }
}