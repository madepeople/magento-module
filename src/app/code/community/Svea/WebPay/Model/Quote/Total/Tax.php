<?php

class Svea_WebPay_Model_Quote_Total_Tax
    extends Mage_Tax_Model_Sales_Total_Quote_Tax
{
    public function __construct()
    {
        $this->setCode('svea_handling_fee_tax');
        $this->_helper      = Mage::helper('tax');
        $this->_calculator  = Mage::getSingleton('tax/calculation');
        $this->_config      = Mage::getSingleton('tax/config');
        $this->_weeeHelper = Mage::helper('weee');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if (!$address->getSveaPaymentFeeAmount()) {
            return $this;
        }

        $this->_setAddress($address);

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

        $methodInstance = $address->getQuote()
            ->getPayment()
            ->getMethodInstance();

        $taxClassId = $methodInstance->getConfigData('handling_fee_tax_class');

        $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClassId));
        if ($rate) {
//            $this->_getAddress()->addTotalAmount('tax', $address->getSveaPaymentFeeTaxAmount());
//            $this->_getAddress()->addBaseTotalAmount('tax', $address->getBaseSveaPaymentFeeTaxAmount());
            $paymentFeeTax = $taxCalculationModel->calcTaxAmount($address->getSveaPaymentFeeAmount(), $rate, true, false);
            $basePaymentFeeTax = $taxCalculationModel->calcTaxAmount($address->getBaseSveaPaymentFeeAmount(), $rate, true, false);

            $address->setSveaPaymentFeeTaxAmount($paymentFeeTax);
            $address->setBaseSveaPaymentFeeTaxAmount($basePaymentFeeTax);
        }

        $paymentFee = $address->getSveaPaymentFeeAmount() - $address->getSveaPaymentFeeTaxAmount();
        $basePaymentFee = $address->getBaseSveaPaymentFeeAmount() - $address->getBaseSveaPaymentFeeTaxAmount();

        $address->setTotalAmount('svea_payment_fee', $paymentFee);
        $address->setBaseTotalAmount('svea_payment_fee', $basePaymentFee);

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        // We don't need this be separate
        return $this;
    }
}