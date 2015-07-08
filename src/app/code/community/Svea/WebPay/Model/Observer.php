<?php

class Svea_WebPay_Model_Observer
{

    /**
     * Add svea payment info that will be displayed when viewing orders
     *
     * Should be run on 'payment_info_block_prepare_specific_information'.
     *
     * Currently only ssn/orgnr is added _if_ the billing country is Finland,
     * because for Finland the getaddress calls are not made.
     *
     */
    public function addSveaPaymentInfo(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        if (!preg_match('/svea/', $payment->getMethod())) {
            return;
        }

        $transport = $observer->getEvent()->getTransport();
        $order = $payment->getOrder();
        if (null === $order || !$order->getId()) {
            return;
        }

        $countryId = $order->getBillingAddress()->getCountryId();

        if (!in_array($payment->getMethod(), array('svea_cardpayment', 'svea_directpayment'))) {
            if ($countryId === 'FI') {
                $helper = Mage::helper('svea_webpay');
                $additionalData = $payment->getAdditionalInformation();
                if ((int)$additionalData['svea_customerType'] === 0) {
                    $ssnLabel = $helper->__('text_ssn');
                    $customerType = $helper->__('private');
                } else {
                    $ssnLabel = $helper->__('text_vat_no');
                    $customerType = $helper->__('company');
                }
                $transport->setData($helper->__('customer_type'), $customerType);
                $transport->setData($ssnLabel, $additionalData['svea_ssn']);
            }
        }
    }

}
