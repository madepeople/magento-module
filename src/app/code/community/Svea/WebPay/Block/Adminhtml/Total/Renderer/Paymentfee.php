<?php
/**
 * We need a renderer to make sure that the value of the payment fee is printed
 * in the order_view totals block in /admin. It's important to realize that
 * this code doesn't *calculate* anything, it simply makes sure that the
 * payment fee is displayed correctly.
 * 
 * The class is used for orders, invoices and creditmemos.
 * 
 * @see Svea_WebPay_Model_Adminhtml_Observer
 */
class Svea_WebPay_Block_Adminhtml_Total_Renderer_Paymentfee
    extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    public function initTotals()
    {
        $payment = $this->getParentBlock()
                ->getOrder()
                ->getPayment();

        if (!preg_match('/svea_invoice/', $payment->getMethod())) {
            return;
        }

        $paymentFee = $payment->getAdditionalInformation('svea_payment_fee');
        if (empty($paymentFee)) {
            return;
        }

        $basePaymentFee = $payment->getAdditionalInformation('base_payment_fee')
                ? : $paymentFee;
        
        $label = Mage::helper('svea_webpay')->__('invoice_fee');

        $total = new Varien_Object(array(
            'code' => $payment->getMethod(),
            'value' => $paymentFee,
            'base_value' => $basePaymentFee,
            'label' => $label
        ));

        $this->getParentBlock()
                ->addTotal($total, 'last');

        return $this;
    }
}