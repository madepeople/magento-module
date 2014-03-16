<?php

/**
 * Include the payment fee in the PDF printout
 *
 * @author jonathan@madepeople.se
 */
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
        if (!($source instanceof Mage_Sales_Model_Order)) {
            $order = $source->getOrder();
        } else {
            $order = $source;
        }
        $paymentFee = $order->getSveaPaymentFeeInclTax();
        return $paymentFee ?: null;
    }
}