<?php

class Svea_WebPay_Model_Adminhtml_Observer
{
    public function setCreditmemoSveaPaymentFee(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $request = $event->getRequest();
        $creditmemo = $event->getCreditmemo();

        $data = $request->getParam('creditmemo');
        if (isset($data['svea_payment_fee_amount'])) {
            $creditmemo->setBaseSveaPaymentFeeAmount((float)$data['svea_payment_fee_amount']);
            foreach ($creditmemo->getConfig()->getTotalModels() as $model) {
                if ($model instanceof Svea_WebPay_Model_Sales_Order_Creditmemo_Total_Paymentfee) {
                    $model->collect($creditmemo);
                }
            }
        }
    }
}