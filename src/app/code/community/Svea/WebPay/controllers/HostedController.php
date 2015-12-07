<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';
class Svea_WebPay_HostedController extends Mage_Core_Controller_Front_Action
{

    protected $_order;
    protected $_xmlResponse;
    protected $_sveaResponseObject;

    /**
     * Parse the Svea response XML
     *
     * @return mixed|SimpleXMLElement|string
     */
    protected function _getSveaResponseXml()
    {
        if (!$this->_xmlResponse) {
            $response = $this->getRequest()
                ->getParam('response');
            $response = @base64_decode($response);
            $response = @simplexml_load_string($response);
            $this->_xmlResponse = $response;
        }
        return $this->_xmlResponse;
    }

    /**
     * Retrieve the Svea response object for the current request, so we can do
     * some admin magic
     *
     * @param $order
     * @return SimpleXMLElement
     */
    protected function _getSveaResponseObject()
    {
        if (!$this->_sveaResponseObject) {
            $order = $this->_initOrder();
            $methodInstance = $order->getPayment()
                ->getMethodInstance();

            $paymentMethodConfig = $methodInstance->getSveaStoreConfClass();
            $config = new SveaMageConfigProvider($paymentMethodConfig);
            $request = $this->getRequest();
            $responseObject = new SveaResponse(array(
                'response' => $request->getParam('response'),
                'mac' => $request->getParam('mac'),
            ), null, $config);
            $this->_sveaResponseObject = $responseObject;
        }
        return $this->_sveaResponseObject;
    }
    /**
     * Initialize the order object for the current transaction
     *
     * @throws Mage_Payment_Exception
     */
    protected function _initOrder()
    {
        if (!$this->_order) {
            $response = $this->_getSveaResponseXml();
            $orderId = (string)$response->transaction->customerrefno;
            if (empty($orderId)) {
                throw new Mage_Payment_Exception('Required field orderId is missing');
            }

            // Lock the order row to prevent double processing from the
            // customer + callback
            $resource = Mage::getModel('sales/order')->getResource();
            $resource->getReadConnection()
                ->select()
                ->forUpdate()
                ->from($resource->getTable('sales/order'))
                ->where('increment_id = ?', $orderId)
                ->query();
            $order = Mage::getModel('sales/order')
                ->loadByIncrementId($orderId);
            if (!$order->getId()) {
                throw new Mage_Payment_Exception('Order with ID "' . $orderId . '" could not be found');
            }

            $methodInstance = $order->getPayment()
                ->getMethodInstance();
            if (!($methodInstance instanceof Svea_WebPay_Model_Hosted_Abstract)) {
                throw new Mage_Payment_Exception('Order isn\'t a Svea order');
            }

            $this->_order = $order;
        }
        return $this->_order;
    }

    /**
     * When Magento claims the order has been successfully placed
     *
     * We save the last_quote_id in a special place to prevent hacky customers
     * from entering checkout/success when they're only on the gateway,
     * confusing merchants, tracking (analytics and affiliates) as well as
     * the hacky customers themselves
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $lastQuoteId = $session->getLastQuoteId();
        $session->unsLastQuoteId();
        if (!$lastQuoteId) {
            // Redirect to the failure page in case of a timeout or hacking
            return $this->_redirect('checkout/onepage/failure');
        }
        $session->setSveaLastQuoteId($lastQuoteId);
        $redirectBlock = $this->getLayout()
            ->createBlock('svea_webpay/payment_hosted_redirect');
        $this->getResponse()->setBody($redirectBlock->toHtml());
    }

    /**
     * When a customer cancels payment in the Svea gateway
     */
    public function cancelAction()
    {
        if ($this->_order) {
            $this->_order->cancel()->save();
        }
        $session = Mage::getSingleton('checkout/session');
        $session->setLastRealOrderId(null);
        $session->unsLastRealOrderId();
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * We have returned from the Svea gateway and they claim everything is
     * epic. Since there is a callback functionality and we need to handle
     * it the same way as this, we just use the callbackAction to process
     * the order information
     */
    public function returnAction()
    {
        try {
            $session = Mage::getSingleton('checkout/session');
            $session->setLastQuoteId($session->getSveaLastQuoteId());
            $session->unsSveaLastQuoteId();
            $this->callbackAction();

            $quote = Mage::getModel('sales/quote')->load($session->getLastQuoteId());
            if ($quote->getId()) {
                // Make sure the quote is disabled, typically for logged in customers
                $quote->setIsActive(false)
                    ->save();
            }

            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } catch (Exception $e) {
            $redirectUrl = 'checkout/onepage/failure';
            $comment = 'CAUTION! This order could have been paid, please inspect the Svea administration panel. Error when returning from gateway: ' . $e->getMessage();
            $message = $e->getMessage();

            try {
                $order = $this->_initOrder();
                $response = $this->_getSveaResponseObject();
                if (!empty($response)) {
                    $responseXml = $this->_getSveaResponseXml();
                    $status = (string)$responseXml->statuscode;
                    switch ($status) {
                        case '108':
                            // Transaction cancelled at the gateway by customer
                            $redirectUrl = 'checkout/cart';
                            $comment = 'Customer cancelled the order at the gateway.';
                            $message = null;
                            break;
                        default:
                            $comment = $response->response->errormessage
                                . ' - ' . $response->response->resultcode
                                . ' - Transaction ID: ' . $response->response->transactionId;
                            $message = $response->response->errormessage;
                            break;
                    }
                }
                $order->addStatusHistoryComment($comment);
                $order->cancel()
                    ->save();
            } catch (Exception $e) {
                // We just don't want to explode in _initOrder
            }

            if (null !== $message) {
                Mage::getSingleton('core/session')->addError($message);
            }
            $this->_redirect($redirectUrl);
        }
    }

    /**
     * Handle the callback information from Svea, needs to be synchronous in
     * case the gateway sends the user to the success page the same time as
     * the Svea callback calls us.
     *
     * We have everything within a transaction with row-locking to prevent
     * race conditions.
     *
     * @return void
     */
    public function callbackAction()
    {
        $write = Mage::getSingleton('core/resource')
            ->getConnection('core_write');
        try {
            $write->beginTransaction();
            $order = $this->_initOrder();
            if ($order->getState() !== Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                // Order is not in pending payment state. It's possible that the payment
                // has already been registered via the callback.
                $write->rollback();
                return;
            }

            $response = $this->_getSveaResponseObject();
            $accepted = $response->response->accepted;
            if ($accepted === 0) {
                // Transaction not accepted
                throw new Exception('Payment failed with code: ' . $response->response->resultcode . '. Please contact Svea for more information.');
            }

            $rawDetails = array();
            foreach ($response->response as $key => $val) {
                if (!is_string($key) || is_object($val)) {
                    continue;
                }
                $rawDetails[$key] = $val;
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($response->response->transactionId)
                ->setIsTransactionApproved(true)
                ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $rawDetails);

            $methodInstance = $order->getPayment()
                ->getMethodInstance();

            if ($methodInstance instanceof Svea_WebPay_Model_Hosted_Direct) {
                $payment->setPreparedMessage('Svea - Payment Successful.');
                $payment->registerCaptureNotification($order->getGrandTotal());
            } else {
                // This config value should come from the payment object data ideally
                // since it could have been changed between requests
                if ($methodInstance->getConfigData('autodeliver')) {
                    // The order should automatically delivered
                    $payment->setPreparedMessage('Svea - Payment Successful.');
                    $payment->capture(null);
                } else {
                    // Implement this somehow, using the new integration library
                    // Leave the transaction open for captures/refunds/etc
                    $payment->setPreparedMessage('Svea - Payment Authorized.');
                    $payment->setIsTransactionClosed(0)
                        ->registerAuthorizationNotification($order->getGrandTotal());
                }
            }

            $newOrderStatus = $methodInstance->getConfigData('new_order_status');
            if (!empty($newOrderStatus)) {
                $order->setStatus($newOrderStatus);
            }
            $order->save();
            $order->sendNewOrderEmail();

            // Newer versions of magento needs this when saving the order
            // inside a transaction, to update the order grid in admin
            $order->getResource()
                ->updateGridRecords(array($order->getId()));

            $write->commit();
        } catch (Exception $e) {
            Mage::logException($e);
            $write->rollback();
            $this->getResponse()
                ->setHttpResponseCode(500);
            throw $e;
        }
    }
}