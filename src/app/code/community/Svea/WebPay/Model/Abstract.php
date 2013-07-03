<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

/**
 * Main class for Payment actions
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
abstract class Svea_WebPay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_canOrder = true;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_isInitializeNeeded = false;
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = false;

    public function isTest()
    {
        return $this->getConfigData('test');
    }

    /**
     * Add values and rows to Svea CreateOrder object
     *
     * @param type $order
     * @param type $additionalInfo
     * @return type Svea CreateOrder object
     */
    public function getSveaPaymentObject($order, $additionalInfo = null)
    {
        $svea = $order->getData('svea_payment_request');

        $billingAddress = $order->getBillingAddress();
        foreach ($order->getAllItems() as $item) {
            $orderRow = Item::orderRow()
                    ->setArticleNumber($item->getProductId())
                    ->setQuantity(get_class($item) == 'Mage_Sales_Model_Quote_Item' ? $item->getQty() : $item->getQtyOrdered())
                    ->setAmountExVat($item->getPrice())
                    ->setName($item->getName())
                    ->setDescription($item->getShortDescription())
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountIncVat($item->getPriceInclTax());

            $svea->addOrderRow($orderRow);
        }

        // Shipping
        if ($order->getShippingAmount() > 0) {
            $shippingIncVat = $order->getShippingAmount() + $order->getShippingTaxAmount();
            $shippingFee = Item::shippingFee()
                    ->setName($order->getShippingDescription())
                    ->setAmountExVat($order->getShippingAmount())
                    ->setAmountIncVat($shippingIncVat);

            $svea->addFee($shippingFee);
        }

        // Discount
        if (abs($order->getDiscountAmount()) > 0) {
            $discountRow = Item::fixedDiscount()
                    ->setAmountIncVat(abs($order->getDiscountAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $svea->addDiscount($discountRow);
        }

        // Gift cards
        if (abs($order->getGiftCardsAmount()) > 0) {
            $giftCardRow = Item::fixedDiscount()
                    ->setAmountIncVat(abs($order->getGiftCardsAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $svea->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFee = $order->getPayment()->getAdditionalInformation('svea_payment_fee');
        $paymentFeeTaxAmount = $order->getPayment()->getAdditionalInformation('svea_payment_fee_tax_amount');

        if ($paymentFee > 0) {
            $invoiceFeeRow = Item::invoiceFee()
                    ->setDescription(Mage::helper('svea_webpay')->__('invoice_fee'))
                    ->setAmountExVat($paymentFee - $paymentFeeTaxAmount)
                    ->setAmountIncVat($paymentFee);

            $svea->addFee($invoiceFeeRow);
        }

        $svea->setCountryCode($billingAddress->getCountryId())
                ->setClientOrderNumber($order->getIncrementId())
                ->setOrderDate(date("Y-m-d"))
                ->setCurrency($order->getOrderCurrencyCode());

        return $svea;
    }

    /**
     * Use Svea IntegrationLib validaton to get Exceptions
     *
     * @return type
     */
    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $order = $paymentInfo->getOrder();
        } else {
            $order = $paymentInfo->getQuote();
        }
        $paymentMethodConfig = $this->getSveaStoreConfClass();
        Mage::helper('svea_webpay')->getPaymentRequest($order, $paymentMethodConfig);
        $sveaRequest = $this->getSveaPaymentObject($order, $paymentInfo->getAdditionalInformation());
        $sveaRequest = $this->_choosePayment($sveaRequest);
        $errors = $sveaRequest->validateOrder();
        if (count($errors) > 0) {
            $exceptionString = "";
            foreach ($errors as $key => $value) {
                $exceptionString .="-" . $key . " : " . $value . "\n";
            }
            Mage::throwException($this->_getHelper()->__($exceptionString));
        }
        return parent::validate();
    }

    public function getSveaStoreConfClass($storeId = null)
    {
        return Mage::getStoreConfig('payment/' . $this->_code, $storeId);
    }
}
