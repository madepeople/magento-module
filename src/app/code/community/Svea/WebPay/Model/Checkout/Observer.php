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

    /**
     * Add additional information to a payment
     *
     * Additional information may come from one of two places $data. It will either
     * be set in $data['svea_info'] or $data[$payment->getMethod()] but never in
     * both.
     *
     * @returns Mage_Sales_Model_Quote_Payment $payment
     */
    protected function _addAdditionalInfoToPayment($data, $payment)
    {
        $paymentMethodCode = $payment->getMethod();

        // Sorry about this but $data is a Varien_Object and it doens't support
        // array_key_exists
        if ($additionalData = $data['svea_info']) {
        } elseif ($additionalData = $data[$paymentMethodCode]) {
        } else {
            $additionalData = array();
        }

        if (empty($additionalData)) {
            return $payment;
        }

        foreach ($additionalData as $key => $value) {
            $payment->setAdditionalInformation($key, $value);
        }

        $method = $data['method'];

        if ($method == 'svea_paymentplan'){
            $payment->setAdditionalInformation('svea_customerType', '0');
            $payment->setAdditionalInformation('campaign', $data[$method]['campaign']);
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
