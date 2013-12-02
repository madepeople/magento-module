<?php

class Svea_WebPay_Model_Invoice_Total_Paymentfee extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        return $this;
        if ($invoice->getOrder()->hasInvoices() != 0) {
            return $this;
        }

        $payment = $invoice->getOrder()->getPayment();
        $paymentFee = $payment->getAdditionalInformation('svea_payment_fee');
        $paymentFeeTaxAmount = $payment->getAdditionalInformation('svea_payment_fee_tax_amount');
        $method = $payment->getMethodInstance()->getCode();

        if (empty($paymentFee) || $method != 'svea_invoice') {
            return;
        }

        // Add tax
        if ($paymentFeeTaxAmount > 0) {
            $paymentFee -= $paymentFeeTaxAmount;
            $invoice->setTaxAmount($invoice->getTaxAmount() + $paymentFeeTaxAmount);
            $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $paymentFeeTaxAmount);
            $invoice->setSubtotal($invoice->getSubtotal() - $paymentFeeTaxAmount);
            $invoice->setBaseSubtotal($invoice->getBaseSubtotal() - $paymentFeeTaxAmount);
        }

        $invoice->setGrandTotal($invoice->getGrandTotal() + $paymentFee);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $paymentFee);

        return $this;
    }
}