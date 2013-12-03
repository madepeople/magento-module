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