<?php

class Svea_WebPay_Model_Quote_Total
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $address;
    protected $paymentMethod;

    public function __construct()
    {
        $this->setCode('svea_payment_fee');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getAddressType() != "shipping") {
            return $this;
        }

        $this->address = $address;

        if ($this->address->getQuote()->getId() == null) {
            return $this;
        }

        $items = $this->address->getAllItems();
        if (!count($items)) {
            return $this;
        }

        $payment = $this->address->getQuote()->getPayment();

        try {
            $this->paymentMethod = $payment->getMethodInstance();
        } catch (Mage_Core_Exception $e) {
            return $this;
        }

        if (!$this->paymentMethod instanceof Mage_Payment_Model_Method_Abstract) {
            return $this;
        }

        if ($this->paymentMethod->getCode() === 'svea_invoice') {
            $fee = $this->paymentMethod->getConfigData('handling_fee');
            $taxClassId = $this->paymentMethod->getConfigData('handling_fee_tax_class');

            if ($fee > 0) {
                $handlingFeeInclVat = $fee;
                $taxRequest = new Varien_Object();
                $taxRequest->setProductClassId($taxClassId);
                $taxRequest->setCustomerClassId($this->address->getQuote()->getCustomerTaxClassId());
                $taxRequest->setCountryId($this->address->getQuote()->getShippingAddress()->getCountry());
                $percent = Mage::getSingleton('tax/calculation')->getRate($taxRequest);

                $taxRate = $percent / 100;
                $handlingFeeTaxAmount = $handlingFeeInclVat - ($handlingFeeInclVat / (1 + $taxRate));
                $handlingFeeExclVat = $handlingFeeInclVat - $handlingFeeTaxAmount;

                $handlingFee = 0;
                if ($taxClassId > 0) {
                    $handlingFee = $handlingFeeInclVat;

                    $address->setExtraTaxAmount($address->getExtraTaxAmount() + $handlingFeeTaxAmount);
                    $address->setBaseExtraTaxAmount($address->getBaseExtraTaxAmount() + $handlingFeeTaxAmount);
                } else {
                    $handlingFee = $handlingFeeExclVat;
                }

                $address->setBaseGrandTotal($address->getBaseGrandTotal() + $handlingFeeExclVat);
                $address->setGrandTotal($address->getGrandTotal() + $handlingFeeExclVat);

                $address->setPaymentFee($handlingFee);
                $address->setPaymentFeeExclVat($handlingFeeExclVat);
            }
        }

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {

        if ($address->getAddressType() != "shipping") {
            return $this;
        }

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