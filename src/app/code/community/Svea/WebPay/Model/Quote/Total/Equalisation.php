<?php

/**
 * The purpose of this totals collector is to even out any difference between
 * the values calculated by Svea systems and Magento. Newer versions, >= 1.9.1
 * and 1.14.1, handle totals calculations much better than previous versions
 * of Magento. However, there are still cases with percentage discounts that
 * yield different grand totals when many products are added.
 *
 * The result of this collector isn't printed anywhere. It's added in the
 * background after the grand total collector has run.
 *
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_Quote_Total_Equalisation
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function __construct()
    {
        $this->setCode('svea_payment_fee');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        $grandTotal     = $address->getGrandTotal();
        $baseGrandTotal = $address->getBaseGrandTotal();

        if ($grandTotal > 0 && $baseGrandTotal > 0) {
            $sveaPaymentFee = $address->getSveaPaymentFee();

            $payment = $address->getQuote()
                ->getPayment();

            if ($payment->getMethod() === null) {
                // No payment chosen yet
                return;
            }

            $methodInstance = $payment->getMethodInstance();
            if (!($methodInstance instanceof Svea_WebPay_Model_Abstract)) {
                // Only modify Svea payments
                return;
            }

            $taxAmount = $address->getTaxAmount();
            $baseTaxAmount = $address->getBaseTaxAmount();

            $sveaRequest = $methodInstance->getSveaPaymentObject($address->getQuote());

            $totalDiff = 10;
            $taxDiff = 10;
            $newGrandTotal = $grandTotal+$totalDiff;
            $newBaseGrandTotal = $baseGrandTotal+$totalDiff;

            $newTaxAmount = $taxAmount+$taxDiff;
            $newBaseTaxAmount = $baseTaxAmount+$taxDiff;

            $address->setGrandTotal($newGrandTotal);
            $address->setBaseGrandTotal($newBaseGrandTotal);
            $address->setTaxAmount($newTaxAmount);
            $address->setBaseTaxAmount($newBaseTaxAmount);
        }
    }
}