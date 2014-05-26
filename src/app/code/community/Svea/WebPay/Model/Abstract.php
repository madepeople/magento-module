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
        //Get Request and billing addres
        $svea = $order->getData('svea_payment_request');
        $billingAddress = $order->getBillingAddress();
        $storeId = $order->getStoreId();
        $store = Mage::app()->getStore($storeId);
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $taxConfig = Mage::getSingleton('tax/config');

        // Build the rows for request
        $totalDiscount = 0;
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }

            // Default to the item price
            $price = $item->getPrice();
            $priceInclTax = $item->getPriceInclTax();
            $taxPercent = $item->getTaxPercent();
            $name = $item->getName();

            $parentItem = $item->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $price = $parentItem->getPrice();
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
            $orderRow = Item::orderRow()
                    ->setArticleNumber($item->getSku())
                    ->setQuantity((int)$qty)
                    ->setName($name)
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setVatPercent((int)$taxPercent);

            if ($taxConfig->priceIncludesTax($storeId)) {
                $orderRow->setAmountIncVat((float)$priceInclTax);
            } else {
                $orderRow->setAmountExVat((float)$price);
            }

            $svea->addOrderRow($orderRow);
        }

        $request = $taxCalculationModel->getRateRequest(
                $order->getShippingAddress(),
                $order->getBillingAddress(),
                null,
                $store);

        // Shipping
        if ($order->getShippingAmount() > 0) {
            $shippingFee = Item::shippingFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName($order->getShippingDescription());

            // We require shipping tax to be set
            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
            $shippingFee->setVatPercent((int)$rate);

            if ($taxConfig->shippingPriceIncludesTax($storeId)) {
                $shippingFee->setAmountIncVat($order->getShippingInclTax());
            } else {
                $shippingFee->setAmountExVat($order->getShippingAmount());
            }

            $svea->addFee($shippingFee);
        }

        // Discount
        if (abs($order->getDiscountAmount()) > 0) {
            $discountRow = Item::fixedDiscount()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountIncVat(abs($order->getDiscountAmount()));

            $svea->addDiscount($discountRow);
        }

        // Gift cards
        if (abs($order->getGiftCardsAmount()) > 0) {
            $giftCardRow = Item::fixedDiscount()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountIncVat(abs($order->getGiftCardsAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $svea->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFee = $order->getSveaPaymentFeeInclTax();

        if ($paymentFee > 0) {
            $paymentFeeTaxClass = $this->getConfigData('handling_fee_tax_class');
            $rate = $taxCalculationModel->getRate($request->setProductClassId($paymentFeeTaxClass));
            $invoiceFeeRow = Item::invoiceFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                    ->setVatPercent((int)$rate)
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
