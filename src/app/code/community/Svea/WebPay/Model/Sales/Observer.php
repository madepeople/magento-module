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
class Svea_WebPay_Model_Sales_Observer
{
    public function autodeliverOrder(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();

        if ($payment->getMethodInstance()->getConfigData('autodeliver')) {
            $payment->capture(null);
        }
    }

    public function setupHostedOrder(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();

        if (preg_match('/^svea_(card|direct)payment$/', $payment->getMethod())) {
            $payment->setIsTransactionPending(true);
        }
    }

    /**
     * This method cleans up old pending_gateway orders as they are probably
     * left over from customers who closed their browsers, lost internet
     * connectivity, etc.
     *
     * @param Varien_Object $observer
     */
    public function cancelOldPendingGatewayOrders($observer)
    {
        $date = date('Y-m-d H:i:s', strtotime('-1 hours'));
        $orderCollection = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('state', Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
            ->addAttributeToFilter('created_at', array('lt' => $date));

        foreach ($orderCollection as $order) {
            if (!$order->canCancel()) {
                continue;
            }

            $method = $order->getPayment()
                ->getMethod();

            if (!preg_match('/svea_(card|direct)payment/', $method)) {
                continue;
            }

            $order->cancel();
            $order->addStatusHistoryComment('The order was automatically cancelled due to more than 1 hour of gateway inactivity.');
            $order->save();
        }
    }
}