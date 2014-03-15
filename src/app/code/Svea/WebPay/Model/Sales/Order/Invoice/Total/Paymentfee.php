<?php

class Svea_WebPay_Model_Invoice_Total_Paymentfee extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $invoice->setSveaPaymentFeeAmount(0);
        $invoice->setBaseSveaPaymentFeeAmount(0);
        $invoice->setSveaPaymentFeeTaxAmount(0);
        $invoice->setBaseSveaPaymentFeeTaxAmount(0);
        $invoice->setSveaPaymentFeeInclTax(0);
        $invoice->setBaseSveaPaymentFeeInclTax(0);

        $orderPaymentFeeAmount = $invoice->getOrder()->getSveaPaymentFeeAmount();
        $baseOrderPaymentFeeAmount = $invoice->getOrder()->getBaseSveaPaymentFeeAmount();
        $paymentFeeInclTax = $invoice->getOrder()->getSveaPaymentFeeInclTax();
        $basePaymentFeeInclTax = $invoice->getOrder()->getBaseSveaPaymentFeeInclTax();
        if ($orderPaymentFeeAmount) {
            foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
                if ($previousInvoice->getSveaPaymentFeeAmount() && !$previousInvoice->isCanceled()) {
                    // Payment fee has already been invoiced
                    return $this;
                }
            }

            $invoice->setSveaPaymentFeeAmount($orderPaymentFeeAmount);
            $invoice->setBaseSveaPaymentFeeAmount($baseOrderPaymentFeeAmount);
            $invoice->setSveaPaymentFeeTaxAmount($invoice->getOrder()->getSveaPaymentFeeTaxAmount());
            $invoice->setBaseSveaPaymentFeeTaxAmount($invoice->getOrder()->getBaseSveaPaymentFeeTaxAmount());
            $invoice->setSveaPaymentFeeInclTax($paymentFeeInclTax);
            $invoice->setBaseSveaPaymentFeeInclTax($basePaymentFeeInclTax);

            $subtotal = $invoice->getSubtotal();
            $baseSubtotal = $invoice->getBaseSubtotal();
            $subtotalInclTax = $invoice->getSubtotalInclTax();
            $baseSubtotalInclTax = $invoice->getBaseSubtotalInclTax();
            $grandTotal = $invoice->getGrandTotal() + $orderPaymentFeeAmount;
            $baseGrandTotal = $invoice->getBaseGrandTotal() + $baseOrderPaymentFeeAmount;
            $totalTax = $invoice->getTaxAmount();
            $baseTotalTax = $invoice->getBaseTaxAmount();

            if ($invoice->isLast()) {
                $subtotalInclTax -= $invoice->getOrder()->getSveaPaymentFeeTaxAmount();
                $baseSubtotalInclTax -= $invoice->getOrder()->getBaseSveaPaymentFeeTaxAmount();
            } else {
                $totalTax += $invoice->getOrder()->getSveaPaymentFeeTaxAmount();
                $baseTotalTax += $invoice->getOrder()->getBaseSveaPaymentFeeTaxAmount();
                $subtotalInclTax += $invoice->getOrder()->getSveaPaymentFeeTaxAmount();
                $baseSubtotalInclTax += $invoice->getOrder()->getBaseSveaPaymentFeeTaxAmount();
                $grandTotal += $invoice->getOrder()->getSveaPaymentFeeTaxAmount();
                $baseGrandTotal += $invoice->getOrder()->getBaseSveaPaymentFeeTaxAmount();
            }

            $invoice->setSubtotal($subtotal);
            $invoice->setBaseSubtotal($baseSubtotal);
            $invoice->setSubtotalInclTax($subtotalInclTax);
            $invoice->setBaseSubtotalInclTax($baseSubtotalInclTax);
            $invoice->setTaxAmount($totalTax);
            $invoice->setBaseTaxAmount($baseTotalTax);
            $invoice->setGrandTotal($grandTotal);
            $invoice->setBaseGrandTotal($baseGrandTotal);
        }

        return $this;
    }
}