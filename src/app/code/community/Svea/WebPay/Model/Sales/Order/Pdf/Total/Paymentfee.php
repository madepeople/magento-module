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
        $source = $this->getSource();
        return $source->getBaseSveaPaymentFeeInclTax() ?: null;
    }
}