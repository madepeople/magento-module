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
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditMemo)
    {
        if ($creditMemo->getOrder()->hasCreditmemos() != 0) {
            return $creditMemo;
        }

        $payment = $creditMemo->getOrder()->getPayment();
        $paymentFee = $payment->getAdditionalInformation('svea_payment_fee');
        $paymentFeeTaxAmount = $payment->getAdditionalInformation('svea_payment_fee_tax_amount');
        $paymentFeeRefunded = $payment->getAdditionalInformation('svea_payment_fee_refunded');
        
        die($paymentFeeRefunded);
        if (empty($paymentFee) || $paymentFeeRefunded == 0) {
            return;
        }

        // Add tax
        if ($paymentFeeTaxAmount > 0) {
            $creditMemo->setTaxAmount($creditMemo->getTaxAmount() + $paymentFeeTaxAmount);
            $creditMemo->setBaseTaxAmount($creditMemo->getBaseTaxAmount() + $paymentFeeTaxAmount);
        }

        $creditMemo->setGrandTotal($creditMemo->getGrandTotal() + $paymentFee);
        $creditMemo->setBaseGrandTotal($creditMemo->getBaseGrandTotal() + $paymentFee);

        return $this;
    }
}