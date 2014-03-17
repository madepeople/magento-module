<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_Sales_Quote_Total_Tax
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function __construct()
    {
        $this->setCode('svea_handling_fee_tax');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        $address->setSveaPaymentFeeTaxAmount(0);
        $address->setBaseSveaPaymentFeeTaxAmount(0);
        if (!$address->getSveaPaymentFeeAmount()) {
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

        $methodInstance = $address->getQuote()
            ->getPayment()
            ->getMethodInstance();

        $paymentFeeTax = 0;
        $basePaymentFeeTax = 0;

        $taxClassId = $methodInstance->getConfigData('payment_fee_tax_class');
        $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClassId));

        if ($rate) {
            $paymentFeeTax = $taxCalculationModel->calcTaxAmount($address->getSveaPaymentFeeAmount(), $rate, true, false);
            $basePaymentFeeTax = $taxCalculationModel->calcTaxAmount($address->getBaseSveaPaymentFeeAmount(), $rate, true, false);
        }

        $paymentFee = $address->getSveaPaymentFeeAmount();
        $basePaymentFee = $address->getBaseSveaPaymentFeeAmount();

        if ($methodInstance->getConfigData('payment_fee_includes_tax')) {
            $paymentFeeInclTax = $paymentFee;
            $basePaymentFeeInclTax = $basePaymentFee;
            $paymentFee -= $paymentFeeTax;
            $basePaymentFee -= $basePaymentFeeTax;
        } else {
            $paymentFeeInclTax = $paymentFee + $paymentFeeTax;
            $basePaymentFeeInclTax = $basePaymentFee + $basePaymentFeeTax;
        }

        $address->setSveaPaymentFeeInclTax($paymentFeeInclTax);
        $address->setBaseSveaPaymentFeeInclTax($basePaymentFeeInclTax);
        $address->setSveaPaymentFeeTaxAmount($paymentFeeTax);
        $address->setBaseSveaPaymentFeeTaxAmount($basePaymentFeeTax);
        $address->setTotalAmount('svea_payment_fee', $paymentFee);
        $address->setBaseTotalAmount('svea_payment_fee', $basePaymentFee);
        $this->_getAddress()->addTotalAmount('tax', $address->getSveaPaymentFeeTaxAmount());
        $this->_getAddress()->addBaseTotalAmount('tax', $address->getBaseSveaPaymentFeeTaxAmount());

        return $this;
    }
}