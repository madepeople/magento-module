<?php
/**
 * We need a renderer to make sure that the value of the payment fee is printed
 * in the order_view totals block in /admin. It's important to realize that
 * this code doesn't *calculate* anything, it simply makes sure that the
 * payment fee is displayed correctly.
 *
 * The class is used for orders, invoices and creditmemos.
 *
 * @see Svea_WebPay_Model_Layout_Observer
 */
class Svea_WebPay_Block_Layout_Total_Renderer_Paymentfee
    extends Mage_Sales_Block_Order_Totals
{
    public function initTotals()
    {
        $order = $this->getParentBlock()
                ->getOrder();
        $payment = $order->getPayment();

        if (!preg_match('/svea_invoice/', $payment->getMethod())) {
            return;
        }

        $parentBlock = $this->getParentBlock();
        if ($parentBlock instanceof Mage_Adminhtml_Block_Sales_Order_Invoice_Totals) {
            if (!$parentBlock->getInvoice()->getId() && $order->getSveaPaymentFeeInvoiced()) {
                return;
            }
            $source = $parentBlock->getInvoice();
        } else if ($parentBlock instanceof Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals) {
            if (!$parentBlock->getCreditmemo()->getId() && $order->getSveaPaymentFeeRefunded() == $order->getSveaPaymentFeeInclTax()) {
                return;
            }
            $source = $parentBlock->getCreditmemo();
        } else {
            $source = $order;
        }

        $paymentFee = (float)$source->getSveaPaymentFeeInclTax();
        $basePaymentFee = (float)$source->getBaseSveaPaymentFeeInclTax();

        if (empty($paymentFee)) {
            return;
        }

        $label = Mage::helper('svea_webpay')->__('invoice_fee');

        $total = new Varien_Object(array(
            'code' => $payment->getMethod(),
            'value' => $paymentFee,
            'base_value' => $basePaymentFee,
            'label' => $label
        ));

        $this->getParentBlock()
                ->addTotal($total, 'subtotal');

        return $this;
    }
}