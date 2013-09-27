<?php

/**
 * Observer to get additional info from checkout
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Model_Checkout_Observer
{

    /**
     * Add svea data to additional_information
     *
     * @param Varien_Event_Observer $observer
     */
    public function addSveaData(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()
                ->getPayment();
        $data = $observer->getEvent()->getInput();
        $payment = $this->_addAdditionalInfoToPayment($data, $payment);

        if ($data['method'] == 'svea_invoice') {
            // Get fee and tax class
            $paymentFee = Mage::getStoreConfig('payment/svea_invoice/handling_fee');
            $paymentFeeTaxId = Mage::getStoreConfig('payment/svea_invoice/handling_fee_tax_class');

            if ($paymentFee > 0) {
                $quote = Mage::getSingleton('checkout/session')->getQuote();

                // Get tax rate for select tax
                $taxRequest = new Varien_Object();
                $taxRequest->setProductClassId($paymentFeeTaxId);
                $taxRequest->setCustomerClassId($quote->getCustomerTaxClassId());
                $taxRequest->setCountryId($quote->getShippingAddress()->getCountry());

                $taxHelper = Mage::getSingleton('tax/calculation');
                $percent = (100 + $taxHelper->getRate($taxRequest)) / 100;
                $paymentFeeTaxAmount = $paymentFee - ($paymentFee / $percent);

                $payment->setAdditionalInformation('svea_payment_fee', $paymentFee);
                $payment->setAdditionalInformation('svea_payment_fee_tax_amount', $paymentFeeTaxAmount);
                $payment->setAdditionalInformation('svea_payment_fee_refunded', 0);
                $payment->setAdditionalInformation('svea_payment_fee_invoiced', 0);
            }
        }
    }

    protected function _addAdditionalInfoToPayment($data, $payment)
    {
        $method = $data['method'];
        if (isset($data[$method . '_ssn'])) {
            $payment
                    ->setAdditionalInformation('svea_ssn', $data[$method . '_ssn']);
        }
        if (isset($data[$method . '_birthDay'])) {
            $payment
                    ->setAdditionalInformation('svea_birthDay', $data[$method . '_birthDay']);
        }
        if (isset($data[$method . '_birthMonth'])) {
            $payment
                    ->setAdditionalInformation('svea_birthMonth', $data[$method . '_birthMonth']);
        }
        if (isset($data[$method . '_birthYear'])) {
            $payment
                    ->setAdditionalInformation('svea_birthYear', $data[$method . '_birthYear']);
        }
        if($method == "svea_paymentplan"){
             $payment->setAdditionalInformation('svea_customerType', "0");
        }  else {
             if (isset($data[$method . '_customerType'])) {
            $payment->setAdditionalInformation('svea_customerType', $data[$method . '_customerType']);
            }
        }


        if (isset($data[$method . '_vatNo'])) {
            $payment
                    ->setAdditionalInformation('svea_vatNo', $data[$method . '_vatNo']);
        }
        if (isset($data[$method . '_initials'])) {
            $payment
                    ->setAdditionalInformation('svea_initials', $data[$method . '_initials']);
        }
        if (isset($data[$method . '_addressSelector'])) {
            $payment
                    ->setAdditionalInformation('svea_addressSelector', $data[$method . '_addressSelector']);
        }

        if (isset($data[$method . '_campaign'])) {
            $payment
                    ->setAdditionalInformation('svea_campaign', $data[$method . '_campaign']);
        }
        return $payment;
    }

    /**
     * Add the possibility to reactivate the quote right after the order has
     * been placed, so the cart isn't emptied until the order is fulfilled
     *
     * @param Varien_Event_Observer $observer
     */
    public function reactivateQuoteBeforeGateway(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $method = $quote->getPayment()->getMethod();

        if (!Mage::getStoreConfig('payment/' . $method . '/clear_cart_on_fulfillment')) {
            return;
        }

        $quote->setIsActive(true);
    }
}
