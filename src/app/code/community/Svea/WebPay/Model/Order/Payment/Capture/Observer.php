<?php
/**
 * Observer that sets Invoice object to payment
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Model_Order_Payment_Capture_Observer
{
    /**
     * The reason we need the invoice present on the payment method instance
     * is that our API calls needs to know which items, among other things
     * that have been invoiced, and not only the total amount.
     * 
     * @param Varien_Event_Observer $observer
     */
    public function setCurrentInvoiceToCapture(Varien_Event_Observer $observer)
    {
        $paymentMethod = $observer->getEvent()
                ->getPayment()
                ->getMethodInstance();

        $invoice = $observer->getEvent()
                ->getInvoice();

        $paymentMethod->setCurrentInvoice($invoice);
    }
}