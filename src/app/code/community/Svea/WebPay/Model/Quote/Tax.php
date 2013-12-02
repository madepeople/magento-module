<?php

class Svea_WebPay_Model_Quote_Tax
    extends Mage_Sales_Model_Quote_Address_Total_Tax
{
    public function __construct()
    {
        $this->setCode('svea_payment_fee_tax');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $collection = $address->getQuote()->getPaymentsCollection();
        if ($collection->count() <= 0
                || $address->getQuote()->getPayment()->getMethod() == null) {
            return $this;
        }

        if ($address->getAddressType() != "shipping") {
            return $this;
        }

        $items = $address->getAllItems();
        if (!count($items)) {
            return $this;
        }

        if ($address->getQuote()->getPayment()->getMethod() !== 'svea_invoice') {
            return $this;
        }

        $methodInstance = $address->getQuote()
            ->getPayment()
            ->getMethodInstance();

        $handlingFee = $methodInstance->getConfigData('handling_fee');
        if (empty($handlingFee)) {
            return $this;
        }

        $custTaxClassId = $address->getQuote()->getCustomerTaxClassId();
        $store = $address->getQuote()->getStore();

        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        /* @var $taxCalculationModel Mage_Tax_Model_Calculation */
        $request = $taxCalculationModel->getRateRequest(
            $address,
            $address->getQuote()->getBillingAddress(),
            $custTaxClassId,
            $store
        );

        $taxClassId = $methodInstance->getConfigData('handling_fee_tax_class');
        if (empty($taxClassId)) {
            return $this;
        }

        $handlingFeeTax = 0;
        $handlingFeeBaseTax = 0;

        $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClassId));
        if ($rate) {
            $handlingFeeTax = $store->roundPrice($InvoiceTax);
            $handlingFeeBaseTax = $store->roundPrice($InvoiceBaseTax);
        }


//        $handlingFeeInclVat = $fee;
//        $taxRequest = new Varien_Object();
//        $taxRequest->setProductClassId($taxClassId);
//        $taxRequest->setCustomerClassId($this->address->getQuote()->getCustomerTaxClassId());
//        $taxRequest->setCountryId($this->address->getQuote()->getShippingAddress()->getCountry());
//        $percent = Mage::getSingleton('tax/calculation')->getRate($taxRequest);
//
//        $taxRate = $percent / 100;
//        $handlingFeeTaxAmount = $handlingFeeInclVat - ($handlingFeeInclVat / (1 + $taxRate));
//        $handlingFeeExclVat = $handlingFeeInclVat - $handlingFeeTaxAmount;
//
//        $handlingFee = 0;
//        if ($taxClassId > 0) {
//            $handlingFee = $handlingFeeInclVat;
//
//            $address->setTaxAmount($address->getTaxAmount() + $handlingFeeTaxAmount);
//            $address->setBaseTaxAmount($address->getBaseTaxAmount() + $handlingFeeTaxAmount);
//
//            $applied = Mage::getSingleton('tax/calculation')->getAppliedRates($taxRequest);
//            $this->_saveAppliedTaxes(
//               $address,
//               $applied,
//               $handlingFeeTaxAmount,
//               $handlingFeeTaxAmount,
//               $taxRate
//            );
//        } else {
//            $handlingFee = $handlingFeeExclVat;
//        }
//
//        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $handlingFee);
//        $address->setGrandTotal($address->getGrandTotal() + $handlingFee);
//
//        $address->setPaymentFee($handlingFee);
//        $address->setPaymentFeeExclVat($handlingFeeExclVat);

        return $this;
    }
}