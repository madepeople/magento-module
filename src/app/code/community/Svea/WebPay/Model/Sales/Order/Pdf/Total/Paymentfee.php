<?php

class Svea_WebPay_Model_Sales_Order_Pdf_Total_Paymentfee
    extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    /**
     * Get Total amount from source
     *
     * @return float
     */
    public function getAmount()
    {
        $payment = $this->getSource()->getOrder()->getPayment();
        $paymentFee = $payment->getAdditionalInformation('svea_payment_fee');
        $method = $payment->getMethodInstance()->getCode();

        if (empty($paymentFee) || $method != 'svea_invoice') {
            return;
        }
        
        return $paymentFee;
    }
}