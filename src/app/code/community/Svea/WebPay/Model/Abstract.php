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
     * Configurable products:
     *  Calculate prices using the parent price, to take price variations into concern
     *
     * Simple products:
     *  Just use their prices as is
     *
     * Bundle products:
     *  Use the simple associated product prices, but we need to know that the
     *  parent of the simple product is actually a bundle product
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
        //Get Request and billing addres
        $svea = $order->getData('svea_payment_request');
        $billingAddress = $order->getBillingAddress();
        $storeId = $order->getStoreId();

        //Build the rows for request
        foreach ($order->getAllItems() as $item) {
            // Do not include the Bundle as product. Only it's products.
            if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                    || $item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }

            // Default to the item price
            $price = $item->getPrice();
            $priceInclTax = $item->getPriceInclTax();
            $taxPercent = $item->getTaxPercent();

            $parentItem = $item->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $price = $parentItem->getPrice();
                        $priceInclTax = $parentItem->getPriceInclTax();
                        $taxPercent = $parentItem->getTaxPercent();
                        break;
                }
            }

            $orderRow = Item::orderRow()
                    ->setArticleNumber($item->getProductId())
                    ->setQuantity(get_class($item) == 'Mage_Sales_Model_Quote_Item' ? (int)$item->getQty() : (int)$item->getQtyOrdered())
                    ->setName($item->getName())
                    ->setDescription($item->getShortDescription())
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setVatPercent((int)$taxPercent);

            if (Mage::getStoreConfig('tax/calculation/price_includes_tax', $storeId)) {
                $orderRow->setAmountIncVat((float)$priceInclTax);
            } else {
                $orderRow->setAmountExVat((float)$price);
            }

            $svea->addOrderRow($orderRow);
        }

        $store = Mage::app()->getStore($storeId);

        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $request = $taxCalculationModel->getRateRequest(
                $order->getShippingAddress(),
                $order->getBillingAddress(),
                null,
                $store);

        // Shipping
        if ($order->getShippingAmount() > 0) {
            $shippingFee = Item::shippingFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName($order->getShippingMethod())
                    ->setDescription($order->getShippingDescription())
                    ->setAmountExVat((float)$order->getShippingAmount());

            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
            $shippingFee->setVatPercent((int)$rate);

            $svea->addFee($shippingFee);
        }

        // Discount
        if (abs($order->getDiscountAmount()) > 0) {
            $discountRow = Item::fixedDiscount()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountIncVat((float)abs($order->getDiscountAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $svea->addDiscount($discountRow);
        }

        // Gift cards
        if (abs($order->getGiftCardsAmount()) > 0) {
            $giftCardRow = Item::fixedDiscount()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountIncVat((float)abs($order->getGiftCardsAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $svea->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFee = $order->getPayment()->getAdditionalInformation('svea_payment_fee');
        $paymentFeeTaxAmount = $order->getPayment()->getAdditionalInformation('svea_payment_fee_tax_amount');

        if ($paymentFee > 0) {
            $invoiceFeeRow = Item::invoiceFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                    ->setAmountExVat((float)($paymentFee - $paymentFeeTaxAmount))
                    ->setAmountIncVat((float)$paymentFee);

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
