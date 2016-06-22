<?php

/**
 * Main class for Hosted payments as Card and Direct payment
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
abstract class Svea_WebPay_Model_Hosted_Abstract extends Svea_WebPay_Model_Abstract
{

    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
    protected $_isGateway = true;
    protected $_isInitializeNeeded = true;
    protected $_canFetchTransactionInfo = true;

    /**
     * Instantiate state and set it to state object
     *
     * @param string $paymentAction
     * @param Varien_Object
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    /**
     *
     * @return type url
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl($this->_sveaUrl);
    }

    abstract protected function _choosePayment($sveaObject, $addressSelector = NULL);

    /**
     * This method is a sad reality in order for the total amount to work in
     * all the different magento versions we support. Calculations differ
     * between systems and we can't at this point send only the grand total
     * value (that would fix things).
     *
     * @param $request
     * @param $order
     * @return mixed
     */
    protected function _equalizeRows($request, $order)
    {
        // Find out what the amount is, and if it differs from the Magento
        // grand total, add it as an equalisation row
        $paymentForm = $request->getPaymentForm();
        $message = @simplexml_load_string($paymentForm->xmlMessage);
        $amount = (int)$message->amount;

        $grandTotal = (int)($order->getBaseGrandTotal()*100);
        if ($amount !== $grandTotal) {
            // The difference can be negative or positive
            $diff = $grandTotal-$amount;
            $diff /= 100;
            $svea = $request->order;
            if ($diff < 0) {
                // Negative, use a discount row
                $row = WebPayItem::fixedDiscount()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountIncVat(abs($diff));
                $svea->addDiscount($row);
            } else {
                // Positive, use a normal order row
                $rate = $this->_getOrderMainTaxRate($order);
                $row = WebPayItem::orderRow()
                    ->setArticleNumber('eq')
                    ->setQuantity(1)
                    ->setName('Magento Equalisation')
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setVatPercent($rate)
                    ->setAmountIncVat($diff);
                $svea->addOrderRow($row);
            }
        }

        return $request;
    }

    /**
     *
     * @return type Svea Payment form object
     */
    public function getSveaPaymentForm()
    {
        $paymentInfo = $this->getInfoInstance();
        $paymentMethodConfig = $this->getSveaStoreConfClass();

        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $order = $paymentInfo->getOrder();
        } else {
            $order = $paymentInfo->getQuote();
        }

        Mage::helper('svea_webpay')->getPaymentRequest($order, $paymentMethodConfig);
        $sveaRequest = $this->getSveaPaymentObject($order);

        $sveaRequest = $this->_choosePayment($sveaRequest);
        $this->_equalizeRows($sveaRequest, $order);
        return $sveaRequest;
    }

    /**
     * Refund (fully) both the card and direct bank payments. Payments need
     * to be captured fully before they can be refunded. Otherwise you will
     * get "Illegal transaction status (105)".
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        list($sveaOrderId,) = explode('-', $payment->getParentTransactionId());
        $order = $payment->getOrder();
        $paymentMethodConfig = $this->getSveaStoreConfClass($order->getStoreId());
        $config = new SveaMageConfigProvider($paymentMethodConfig);

        $countryId = $order->getBillingAddress()->getCountryId();
        $creditAmount = $amount * 100;

        $creditTransaction = new Svea\HostedService\CreditTransaction($config);
        $creditTransaction->transactionId = $sveaOrderId;
        $creditTransaction->creditAmount = $creditAmount;
        $creditTransaction->countryCode = $countryId;
        $response = $creditTransaction->doRequest();

        if ($response->accepted === 0) {
            $message = 'CreditTransaction failed for transaction ' . $sveaOrderId . ': ' . $response->errormessage . ' (' . $response->resultcode . ')';
            Mage::throwException($message);
        }

        $result = $this->_flatten($response);
        $transactionString = $sveaOrderId . '-refund-' . ((int)microtime(true));

        $payment->setTransactionId($transactionString)
            ->setIsTransactionClosed(true)
            ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $result);

        return $this;
    }

    /**
     * Validate through Svea integrationLib only if this is an order
     *
     * @return boolean
     */
    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();

        // If quote, skip validation
        if ($paymentInfo instanceof Mage_Sales_Model_Quote_Payment) {
            return true;
        }

        return parent::validate();
    }

}
