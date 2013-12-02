<?php

class Svea_WebPay_Model_Quote_Total extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function __construct()
    {
        $this->setCode('svea_payment_fee');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $address->setPaymentFee(0);
        $address->setPaymentFeeExclVat(0);

        $collection = $address->getQuote()->getPaymentsCollection();
        if ($collection->count() <= 0
                || $address->getQuote()->getPayment()->getMethod() == null) {
            return $this;
        }
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getPaymentFee() > 0) {
            $total = array(
                'code' => $this->getCode(),
                'title' => Mage::helper('svea_webpay')->__('invoice_fee'),
                'value' => $address->getPaymentFee()
            );
            $address->addTotal($total);
        }

        return $this;
    }
}