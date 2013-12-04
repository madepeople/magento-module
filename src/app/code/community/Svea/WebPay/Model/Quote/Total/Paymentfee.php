<?php

class Svea_WebPay_Model_Quote_Total_Paymentfee extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function __construct()
    {
        $this->setCode('svea_payment_fee');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
        
        $address->setPaymentFee(0);
        $address->setBasePaymentFee(0);
        $address->setPaymentFeeTax(0);
        $address->setBasePaymentFeeTax(0);
        $this->_setAmount(0)
            ->_setBaseAmount(0);

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

        $baseHandlingFee = $methodInstance->getConfigData('handling_fee');
        if (empty($baseHandlingFee)) {
            return $this;
        }
  
        $handlingFee = $address->getQuote()->getStore()->convertPrice($baseHandlingFee, false);
        $address->setPaymentFee($handlingFee);
        $address->setBasePaymentFee($baseHandlingFee);
        
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
        
        $rate = $taxCalculationModel->getRate($request->setProductClassId($taxClassId));
        if ($rate) {
            $handlingFeeTax = $taxCalculationModel->calcTaxAmount($address->getPaymentFee(), $rate, true, false);
            $baseHandlingFeeTax = $taxCalculationModel->calcTaxAmount($address->getBasePaymentFee(), $rate, true, false);
            
            $address->setPaymentFeeTax($handlingFeeTax);
            $address->setBasePaymentFeeTax($baseHandlingFeeTax);
        }

        $this->_setAmount($handlingFee - $address->getPaymentFeeTax())
            ->_setBaseAmount($baseHandlingFee - $address->getBasePaymentFeeTax());

        return $this;
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