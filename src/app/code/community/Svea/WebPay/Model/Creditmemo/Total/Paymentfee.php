<?php
/**
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Model_Creditmemo_Total_Paymentfee
    extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $creditmemo->setSveaPaymentFeeAmount(0);
        $creditmemo->setBaseSveaPaymentFeeAmount(0);
        $creditmemo->setSveaPaymentFeeTaxAmount(0);
        $creditmemo->setBaseSveaPaymentFeeTaxAmount(0);
        $creditmemo->setSveaPaymentFeeInclTax(0);
        $creditmemo->setBaseSveaPaymentFeeInclTax(0);

        $order = $creditmemo->getOrder();
        if (!$order->getSveaPaymentFeeAmount()) {
            return $this;
        }

        if ($order->getSveaPaymentFeeRefunded() > 0) {
            // Already refunded
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
            $paymentFeeTaxInclTax = $source->getSveaPaymentFeeInclTax();
            $basePaymentFeeInclTax = $source->getBaseSveaPaymentFeeInclTax();
        }

        $taxAmount += $paymentFeeTaxAmount;
        $baseTaxAmount += $basePaymentFeeTaxAmount;
        $grandTotal += $paymentFeeTaxInclTax;
        $baseGrandTotal += $basePaymentFeeInclTax;

        $creditmemo->setSveaPaymentFeeAmount($paymentFeeAmount);
        $creditmemo->setBaseSveaPaymentFeeAmount($basePaymentFeeAmount);
        $creditmemo->setSveaPaymentFeeTaxAmount($paymentFeeTaxAmount);
        $creditmemo->setBaseSveaPaymentFeeTaxAmount($basePaymentFeeTaxAmount);
        $creditmemo->setSveaPaymentFeeInclTax($paymentFeeTaxInclTax);
        $creditmemo->setBaseSveaPaymentFeeInclTax($basePaymentFeeInclTax);
        $creditmemo->setTaxAmount($taxAmount);
        $creditmemo->setBaseTaxAmount($baseTaxAmount);
        $creditmemo->setGrandTotal($grandTotal);
        $creditmemo->setBaseGrandTotal($baseGrandTotal);

        return $this;
    }
}