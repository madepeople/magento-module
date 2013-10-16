<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_Observer
{
    /**
     * We need to know exactly which invoice we're messing around with when
     * capture occurs, otherwise we can't fetch the correct information
     *
     * @param Varien_Event_Observer $observer
     */
    public function setCurrentInvoiceOnOrder(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getPayment()->getOrder();
        $invoice = $observer->getEvent()->getInvoice();
        $order->setCurrentInvoice($invoice);
    }
}