<?php
/**
 * Layout related event listeners
 */
class Svea_WebPay_Model_Layout_Observer
{
    /**
     * Make sure that the custom payment fee block renders correctly by the
     * totals in the order, invoice and creditmemo totals
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function addPaymentFeeTotalToBlock(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (!in_array($block->getNameInLayout(),
                array('order_totals', 'invoice_totals', 'creditmemo_totals'))) {
            return;
        }

        $renderer = $block->getLayout()
                ->createBlock('svea_webpay/layout_total_renderer_paymentfee');

        $block->setChild('svea_payment_fee_renderer', $renderer);
    }
}