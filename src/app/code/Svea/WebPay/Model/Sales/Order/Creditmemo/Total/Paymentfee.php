<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_Sales_Order_Creditmemo_Total_Paymentfee
    extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
//        $creditmemo->setSveaPaymentFeeAmount(0);
//        $creditmemo->setBaseSveaPaymentFeeAmount(0);
//        $creditmemo->setSveaPaymentFeeTaxAmount(0);
//        $creditmemo->setBaseSveaPaymentFeeTaxAmount(0);
//        $creditmemo->setSveaPaymentFeeInclTax(0);
//        $creditmemo->setBaseSveaPaymentFeeInclTax(0);

        $order = $creditmemo->getOrder();
        if (!$order->getSveaPaymentFeeAmount()) {
            return $this;
        }

        $paymentFeeAmount = 0;
        $basePaymentFeeAmount = 0;
        $paymentFeeTaxAmount = 0;
        $basePaymentFeeTaxAmount = 0;
        $paymentFeeTaxInclTax = 0;

        $grandTotal = $creditmemo->getGrandTotal();
        $baseGrandTotal = $creditmemo->getBaseGrandTotal();
        $taxAmount = $creditmemo->getTaxAmount();
        $baseTaxAmount = $creditmemo->getBaseTaxAmount();

        if ($invoice = $creditmemo->getInvoice()) {
            if ($invoice->getSveaPaymentFeeAmount()) {
                // Refund specific invoice
                $source = $invoice;
            }
        } else if (!$order->getSveaPaymentFeeRefunded()) {
            // Refund from order values
            $source = $order;
        }

        if (isset($source)) {
            $paymentFeeAmount = $source->getSveaPaymentFeeAmount();
            $basePaymentFeeAmount = $source->getBaseSveaPaymentFeeAmount();
            $paymentFeeTaxAmount = $source->getSveaPaymentFeeTaxAmount();
            $basePaymentFeeTaxAmount = $source->getBaseSveaPaymentFeeTaxAmount();
            $paymentFeeInclTax = $source->getSveaPaymentFeeInclTax();
            $basePaymentFeeInclTax = $source->getBaseSveaPaymentFeeInclTax();

            if ($creditmemo->hasBaseSveaPaymentFeeAmount()) {
                $isInclTax = Mage::getSingleton('tax/config')->displaySalesPricesInclTax($order->getStoreId());
                $sourceBasePaymentFeeAmount = Mage::app()->getStore()->roundPrice($creditmemo->getBaseSveaPaymentFeeAmount());
                if ($isInclTax && $sourceBasePaymentFeeAmount != 0) {
                    $taxAmount -= $paymentFeeTaxAmount;
                    $baseTaxAmount -= $basePaymentFeeTaxAmount;
                    $grandTotal -= $paymentFeeInclTax;
                    $baseGrandTotal -= $basePaymentFeeInclTax;

                    $part = $sourceBasePaymentFeeAmount/$basePaymentFeeInclTax;
                    $paymentFeeInclTax = Mage::app()->getStore()->roundPrice($paymentFeeInclTax*$part);
                    $basePaymentFeeInclTax = $sourceBasePaymentFeeAmount;
                    $paymentFeeAmount = Mage::app()->getStore()->roundPrice($basePaymentFeeAmount*$part);
                    $paymentFeeTaxAmount = $paymentFeeInclTax - $paymentFeeAmount;
                    $basePaymentFeeTaxAmount = $basePaymentFeeInclTax - $basePaymentFeeAmount;
                }
            }

            $taxAmount += $paymentFeeTaxAmount;
            $baseTaxAmount += $basePaymentFeeTaxAmount;
            $grandTotal += $paymentFeeInclTax;
            $baseGrandTotal += $basePaymentFeeInclTax;
        }

        $creditmemo->setSveaPaymentFeeAmount($paymentFeeAmount);
        $creditmemo->setBaseSveaPaymentFeeAmount($basePaymentFeeAmount);
        $creditmemo->setSveaPaymentFeeTaxAmount($paymentFeeTaxAmount);
        $creditmemo->setBaseSveaPaymentFeeTaxAmount($basePaymentFeeTaxAmount);
        $creditmemo->setSveaPaymentFeeInclTax($paymentFeeInclTax);
        $creditmemo->setBaseSveaPaymentFeeInclTax($basePaymentFeeInclTax);
        $creditmemo->setTaxAmount($taxAmount);
        $creditmemo->setBaseTaxAmount($baseTaxAmount);
        $creditmemo->setGrandTotal($grandTotal);
        $creditmemo->setBaseGrandTotal($baseGrandTotal);

        return $this;
    }
}