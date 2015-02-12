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
     * There are loads of different types of gift cards, we try to handle
     * all of them in here
     * in here
     *
     * @param $order
     * @return number
     */
    protected function _getGiftCardAmount($order)
    {
        if ($order instanceof Mage_Sales_Model_Quote) {
            $quote = $order;
            $giftCardAmount = abs($quote->getGiftCardsAmount());
        } else {
            // EE gift cards
            $giftCardAmount = abs($order->getGiftCardsAmount());

            // Unirgy gift cards
        }

        return $giftCardAmount;
    }

    /**
     * Returns the main tax percentage of the order
     *
     * @param $order
     * @return int
     */
    protected function _getOrderMainTaxRate($order)
    {
        if ($order instanceof Mage_Sales_Model_Quote) {
            // We need to sort out the terminology in the future version
            $quote = $order;
            $totals = $quote->getTotals();
            $tax = $totals['tax']->getFullInfo();
            $quoteTax = array_shift($tax);
            $rate = $quoteTax['percent'];
        } else {
            $appliedTaxes = $order->getAppliedTaxes();
            if (null === $appliedTaxes) {
                $tax = Mage::getModel('sales/order_tax')->load($order->getId(), 'order_id');
                $rate = $tax->getPercent();
            } else {
                $orderTax = array_shift($appliedTaxes);
                $rate = $orderTax['percent'];
            }
        }
        return (int)$rate;
    }

    /**
     * Add values and rows to Svea CreateOrder object
     *
     * Configurable products:
     *  Calculate prices using the parent price, to take price variations into concern
     *
     * Simple products:
     *  Just use their prices as is
     *
     * Bundle products:
     *  The main parent product has the price, but the associated products
     *  need to be transferred on separate 0 amount lines so the invoice is
     *  verbose enough
     *
     * Grouped products:
     *  These are treated the same way as simple products
     *
     * @param type $order
     * @param type $additionalInfo
     * @return type Svea CreateOrder object
     */
    public function getSveaPaymentObject($order, $additionalInfo = null)
    {
        $paymentMethodConfig = $this->getSveaStoreConfClass();
        $svea = Mage::helper('svea_webpay')->getPaymentRequest($order, $paymentMethodConfig);

        $billingAddress = $order->getBillingAddress();
        $storeId = $order->getStoreId();
        $store = Mage::app()->getStore($storeId);
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $taxConfig = Mage::getSingleton('tax/config');

        // Build the rows for request
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }

            // Default to the item price
            $price = $item->getPrice();
            $priceInclTax = $item->getPriceInclTax();
            $taxPercent = $item->getTaxPercent();
            if (!(int)$taxPercent) {
                // If it's a bundle item we have to calculate the tax from
                // the including/excluding tax values
                $taxPercent = round(100*(($priceInclTax/$price)-1));
            }
            $name = $item->getName();

            $parentItem = $item->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $priceInclTax = $parentItem->getPriceInclTax();
                        $taxPercent = $parentItem->getTaxPercent();
                        break;
                    case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                        $taxPercent = $priceInclTax = $price = 0;
                        $name = '- ' . $name;
                        break;
                }
            }

            $qty = get_class($item) == 'Mage_Sales_Model_Quote_Item' ? $item->getQty() : $item->getQtyOrdered();

            $orderRow = WebPayItem::orderRow()
                ->setArticleNumber($item->getSku())
                ->setQuantity((int)$qty)
                ->setName($name)
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setVatPercent((int)$taxPercent)
                ->setAmountIncVat((float)$priceInclTax);

            $svea->addOrderRow($orderRow);
        }

        $request = $taxCalculationModel->getRateRequest(
            $order->getShippingAddress(),
            $order->getBillingAddress(),
            null,
            $store);

        if ($order instanceof Mage_Sales_Model_Quote) {
            $quote = $order;
            $totals = $quote->getTotals();
            $discount = abs($totals['discount']->getValue());
            $shipping = abs($totals['shipping']->getAddress()
                ->getShippingInclTax());
            if (isset($totals['svea_payment_fee'])) {
                $paymentFee = $totals['svea_payment_fee'];
                $paymentFeeInclTax = $paymentFee->getAddress()
                    ->getSveaPaymentFeeInclTax();
            }  else {
                $paymentFeeInclTax = 0;
            }
        } else {
            $discount = abs($order->getDiscountAmount());
            $shipping = abs($order->getShippingInclTax());
            $paymentFeeInclTax = abs($order->getSveaPaymentFeeInclTax());
        }

        $giftCardAmount = $this->_getGiftCardAmount($order);

        // Shipping
        if ($shipping > 0) {
            $shippingFee = WebPayItem::shippingFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName($order->getShippingDescription());

            // We require shipping tax to be set
            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
            $shippingFee->setVatPercent((int)$rate);
            $shippingFee->setAmountIncVat($shipping);
            $svea->addFee($shippingFee);
        }

        // Discount
        if ($discount > 0) {
            if (!$taxConfig->applyTaxAfterDiscount($order->getStoreId())) {
                $rate = $this->_getOrderMainTaxRate($order);

                // Round this to two decimals using magento rounding functions
                $discount *= 1+($rate/100);
                $calculator = Mage::getModel('core/calculator', $order->getStore());
                $discount = $calculator->deltaRound($discount, true);
            }

            $discountRow = WebPayItem::fixedDiscount()
                ->setName(Mage::helper('svea_webpay')->__('discount'))
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setAmountIncVat($discount);

            $svea->addDiscount($discountRow);
        }

        // Gift cards
        if ($giftCardAmount > 0) {
            $giftCardRow = WebPayItem::fixedDiscount()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setAmountIncVat(abs($order->getGiftCardsAmount()));

            $svea->addDiscount($giftCardRow);
        }

        // Invoice fee
        if ($paymentFeeInclTax > 0) {
            $paymentFeeTaxClass = $this->getConfigData('handling_fee_tax_class');
            $rate = $taxCalculationModel->getRate($request->setProductClassId($paymentFeeTaxClass));
            $invoiceFeeRow = WebPayItem::invoiceFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                ->setVatPercent((int)$rate);

            $invoiceFeeRow->setAmountIncVat((float)$paymentFeeInclTax);
            $svea->addFee($invoiceFeeRow);
        }


        $svea->setCountryCode($billingAddress->getCountryId())
            ->setClientOrderNumber($order->getIncrementId())
            ->setOrderDate(date("Y-m-d"))
            ->setCurrency($order->getOrderCurrencyCode());
        return $svea;
    }

    public function getSveaStoreConfClass($storeId = null)
    {
        return Mage::getStoreConfig('payment/' . $this->_code, $storeId);
    }

    /**
     * Returns the deliver order request that we use to capture transactions
     *
     * @param Mage_Payment_Model_Info $payment
     * @param $transactionId
     * @return mixed
     */
    protected function _getDeliverOrderRequest(Mage_Payment_Model_Info $payment, $transactionId)
    {
        $order = $payment->getOrder();
        $paymentMethodConfig = $this->getSveaStoreConfClass($order->getStoreId());
        $config = new SveaMageConfigProvider($paymentMethodConfig);
        $countryId = $order->getBillingAddress()->getCountryId();

        $request = WebPayAdmin::deliverOrderRows($config)
            ->setOrderId($transactionId)
            ->setCountryCode($countryId)
        ;

        return $request;
    }

    /**
     * Builds the base queryOrder object
     *
     * @param Mage_Payment_Model_Info $payment
     * @param $transactionId
     * @return mixed
     */
    protected function _getQueryOrderRequest(Mage_Payment_Model_Info $payment, $transactionId)
    {
        $order = $payment->getOrder();
        $paymentMethodConfig = $this->getSveaStoreConfClass($order->getStoreId());
        $config = new SveaMageConfigProvider($paymentMethodConfig);
        $countryId = $order->getBillingAddress()->getCountryId();

        $request = WebPayAdmin::queryOrder($config)
            ->setOrderId($transactionId)
            ->setCountryCode($countryId)
        ;

        return $request;
    }

    /**
     * Builds the base queryOrder object
     *
     * @param Mage_Payment_Model_Info $payment
     * @param $transactionId
     * @return mixed
     */
    protected function _getCancelOrderRequest(Mage_Payment_Model_Info $payment, $transactionId)
    {
        $order = $payment->getOrder();
        $paymentMethodConfig = $this->getSveaStoreConfClass($order->getStoreId());
        $config = new SveaMageConfigProvider($paymentMethodConfig);
        $countryId = $order->getBillingAddress()->getCountryId();

        $request = WebPayAdmin::cancelOrder($config)
            ->setOrderId($transactionId)
            ->setCountryCode($countryId)
        ;

        return $request;
    }

    /**
     * Flattens an array
     *
     * @param $array
     */
    protected function _flattenArray($array, $prefix = '')
    {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->_flattenArray($value, $prefix.$key.'.'));
            } else {
                $result[$prefix.$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Flattens an array or object into an array
     *
     * @param $object  array|StdClass
     */
    protected function _flatten($object)
    {
        $array = Mage::helper('core')->jsonDecode(
            Mage::helper('core')->jsonEncode($object),
            Zend_Json::TYPE_ARRAY);

        $flattenedArray = $this->_flattenArray($array);
        return $flattenedArray;
    }

}
