<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';
class Svea_WebPay_HostedController extends Mage_Core_Controller_Front_Action
{

    public function redirectCardAction()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $order = Mage::getModel('sales/order')->load($orderId);

        if (!$orderId) {
            return $this->_redirect('');
        }

        if (!$order->getId()) {
            return $this->_redirect('');
        }

        if (!($order->getPayment()->getMethodInstance() instanceof Svea_WebPay_Model_Hosted_Abstract)) {
            return $this->_redirect('');
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function redirectDirectAction()
    {

        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $order = Mage::getModel('sales/order')->load($orderId);

        if (!$orderId) {
            return $this->_redirect('');
        }

        if (!$order->getId()) {
            return $this->_redirect('');
        }

        if (!($order->getPayment()->getMethodInstance() instanceof Svea_WebPay_Model_Hosted_Abstract)) {
            return $this->_redirect('');
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function responseCardAction()
    {
        if ($this->getRequest()->getParam("response") && $this->getRequest()->getParam("mac")) {
            $conf = Mage::getStoreConfig('payment/svea_cardpayment');
            $this->responseAction($_REQUEST, $conf);

            $quote = Mage::getModel('checkout/session')
                ->getQuote();

            if ($quote && $quote->getId()) {
                $quote->setIsActive(false)
                    ->save();
            }
        }
    }

    public function responseDirectPaymentAction()
    {
        if ($this->getRequest()->getParam("response") && $this->getRequest()->getParam("mac")) {
            $conf = Mage::getStoreConfig('payment/svea_directpayment');
            $this->responseAction($_REQUEST, $conf);

            $quote = Mage::getModel('checkout/session')
                ->getQuote();

            if ($quote && $quote->getId()) {
                $quote->setIsActive(false)
                    ->save();
            }
        }
    }

    private function responseAction($request, $conf)
    {
        $sveaConf = new SveaMageConfigProvider($conf);
        $response = new SveaResponse($request, "", $sveaConf);

        $order = Mage::getModel('sales/order')
                ->loadByIncrementId($response->response->clientOrderNumber);

        if (!$order->getId()) {
            Mage::getSingleton('core/session')  ->addError("Order #" . $response->response->clientOrderNumber . " couldn't be loaded")
                                                ->addError( Mage::helper('svea_webpay')->responseCodes($response->response->resultcode, $response->response->errormessage));
            return $this->_redirect("checkout/onepage/failure", array("_secure" => true));
        }

        if ($order->getTotalDue() == 0) {
            // The order has already been paid, is somebody messing with us?
            Mage::getSingleton('core/session')  ->addError("Order #" . $response->response->clientOrderNumber . " has already been paid")
                                                ->addError( Mage::helper('svea_webpay')->responseCodes($response->response->resultcode, $response->response->errormessage));

            return $this->_redirect("checkout/onepage/failure", array("_secure" => true));
        }

        if ($response->response->accepted == 1) {
            $payment = $order->getPayment();
            $payment->addTransaction(Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE);
            $payment->setPreparedMessage('Order has been paid at Svea.')
                    ->setTransactionId($response->response->transactionId)
                    ->setIsTransactionApproved(true);

            $rawDetails = array();
            foreach ($response->response as $key => $val) {
                if (!is_string($key)) {
                    continue;
                }

                $rawDetails[$key] = $val;
            }

            $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $rawDetails);
            $payment->registerCaptureNotification($response->response->amount);

            $newOrderStatus = $order->getPayment()
                ->getMethodInstance()
                ->getConfigData('new_order_status');

            if (!empty($newOrderStatus)) {
                $order->setStatus($newOrderStatus);
            }

            $order->save();
            $order->sendNewOrderEmail();

            $this->_redirect("checkout/onepage/success", array("_secure" => true));
        } else {
            $errorMessage = $response->response->errormessage;
            $statusCode = $response->response->resultcode;

            if ($order->canCancel()) {
                $order->cancel();
                $order->addStatusToHistory($order->getStatus(), Mage::helper('svea_webpay')->responseCodes($statusCode, $errorMessage), false);
                $order->save();
            }

            Mage::getSingleton('core/session')->addError(Mage::helper('svea_webpay')->responseCodes($statusCode, $errorMessage));

            $this->_redirect("checkout/onepage/failure", array("_secure" => true));
        }
    }
}