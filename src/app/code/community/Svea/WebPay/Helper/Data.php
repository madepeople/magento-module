<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

/**
 * Helper functions
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Get Addresses from Svea API
     *
     * @param type $ssn
     * @param type $countryCode
     * @param type $conf
     * @return type
     */
    public function getAddresses($ssn, $countryCode, $conf)
    {
        $sveaconfig = new SveaMageConfigProvider($conf);
        $addressRequest = WebPay::getAddresses($sveaconfig)
                ->setOrderTypeInvoice()
                ->setCountryCode($countryCode);

        if ($conf['company'] == true) {
            $addressRequest->setCompany($ssn);
        } else {
            $addressRequest->setIndividual($ssn);
        }

        return $addressRequest->doRequest();
    }

    /**
     * Creates Svea Create order object with Config auth values
     *
     * @param type $order
     * @param type Config $auth
     * @return Svea CreateOrder object
     */
    public function getPaymentRequest($order, $auth, $force = false)
    {
        if (!$order->hasData('svea_payment_request') || $force) {
            $conf = new SveaMageConfigProvider($auth);
            $sveaObj = WebPay::createOrder($conf);
            $order->setData('svea_payment_request', $sveaObj);
        }

        return $order->getData('svea_payment_request');
    }

    /**
     * Calls from checkout frontend
     *
     * @return Calculated params array
     */
    public function getPaymentPlanParams($quote = null)
    {
        if ($quote === null) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
        }

        $orderTotal = $quote->getGrandTotal() - $quote->getShippingAmount();
        $params = Mage::getModel('svea_webpay/paymentplan')->getCollection();

        $latestTimestamp = $this->getLatestUpdateOfPaymentPlanParams($quote->getStoreId());

        // Get most recent and filter out campaigns that does not fit the
        // order amount
        $paramsArray = array();
        foreach ($params as &$cc) {
            if ($cc->timestamp == $latestTimestamp) {
                if ($orderTotal >= $cc->fromamount && $orderTotal <= $cc->toamount) {
                    $cc->monthlyamount = round($orderTotal * $cc->monthlyannuityfactor);
                    $paramsArray[] = $cc;
                }
            }
        }

        return (object) $paramsArray;
    }

    /**
     * To present last updated in PaymentPlan payment view next to button
     *
     * @return string
     */
    public function getLatestUpdateOfPaymentPlanParams($storeId = null)
    {
        $collection = Mage::getModel('svea_webpay/paymentplan')->getCollection()
                ->setOrder('timestamp', Varien_Data_Collection::SORT_ORDER_DESC);

        if (null !== $storeId) {
            $collection->addFieldToFilter('storeId', $storeId);
        }

        if (!$collection->count()) {
            return 'Never';
        }

        return $collection->getIterator()
                ->current()
                ->getTimestamp();
    }

    /**
     * Call model to update payment plan params
     *
     * @return string
     */
    public function updatePaymentPlanParams($id = null, $scope = null)
    {
        $paramModel = Mage::getModel('svea_webpay/paymentplan');
        $paramModel->updateParamTable($id, $scope);
        return date('Y-m-d H:i:s');
    }

    /**
     * Builds request for DeliverInvoice for Invoice and PaymentPlan payment.
     * Calls from Magento Capture
     *
     * @param type $invoice
     * @param type $auth
     * @param type $sveaOrderId
     * @return type
     */
    public function getDeliverInvoiceRequest($invoice, $auth, $sveaOrderId)
    {
        $conf = new SveaMageConfigProvider($auth);
        $sveaObject = WebPay::deliverOrder($conf);
        $order = $invoice->getOrder();
        $countryCode = $order->getBillingAddress()->getCountryId();
        $storeId = $order->getStoreId();
        $store = Mage::app()->getStore($storeId);
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $taxConfig = Mage::getSingleton('tax/config');

        // Add invoiced items
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }

            // Default to the item price
            $price = $orderItem->getPrice();
            $priceInclTax = $orderItem->getPriceInclTax();
            $taxPercent = $orderItem->getTaxPercent();
            $name = $item->getName();

            $parentItem = $orderItem->getParentItem();
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

            switch (get_class($item)) {
                case 'Mage_Sales_Model_Quote_Item':
                case 'Mage_Sales_Model_Order_Invoice_Item':
                case 'Mage_Sales_Model_Order_Creditmemo_Item':
                    $qty = $item->getQty();
                    break;
                default:
                    $qty = $item->getQtyOrdered();
                    break;
            }

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

            $sveaObject->addOrderRow($orderRow);
        }

        $request = $taxCalculationModel->getRateRequest(
                $order->getShippingAddress(),
                $order->getBillingAddress(),
                null,
                $store);

        // Add shipping fee
        if ($invoice->getShippingAmount() > 0) {
            $shippingFee = Item::shippingFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName($order->getShippingDescription());

            // We require shipping tax to be set
            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
            $shippingFee->setVatPercent((int)$rate);

            if ($taxConfig->shippingPriceIncludesTax($storeId)) {
                $shippingFee->setAmountIncVat($invoice->getShippingInclTax());
            } else {
                $shippingFee->setAmountExVat($invoice->getShippingAmount());
            }

            $sveaObject->addFee($shippingFee);
        }

        // Possible discount
        if (abs($invoice->getDiscountAmount())) {
            $discountRow = Item::fixedDiscount()
                    ->setAmountIncVat(abs($invoice->getDiscountAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($discountRow);
        }

        // Gift card(s)
        if (abs($order->getGiftCardsAmount())) {
            $giftCardRow = Item::fixedDiscount()
                    ->setAmountIncVat(abs($order->getGiftCardsAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFee = $invoice->getSveaPaymentFeeAmount();
        $paymentFeeInclTax = $invoice->getSveaPaymentFeeInclTax();
        $invoiced = $invoice->getOrder()->getSveaPaymentFeeInvoiced();

        if ($paymentFee > 0 && $invoiced == 0) {
            $invoiceFee = Item::invoiceFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                    ->setAmountExVat($paymentFee)
                    ->setAmountIncVat($paymentFeeInclTax);

            $sveaObject->addFee($invoiceFee);
            $invoice->getOrder()->setSveaPaymentFeeInvoiced($paymentFeeInclTax);
        }

        $sveaObject = $sveaObject->setCountryCode($countryCode)
                ->setOrderId($sveaOrderId)
                ->setInvoiceDistributionType(Mage::getStoreConfig("payment/svea_invoice/deliver_method"));

        $invoice->setData('svea_deliver_request', $sveaObject);

        return $invoice->getData('svea_deliver_request');
    }


    /**
     * Builds request for DeliverInvoice as Credit for Invoice. Calls from
     * Magento Refund
     *
     * @param type $payment
     * @param type $auth
     * @param type $sveaOrderId
     * @return type
     */
    public function getRefundRequest($payment, $auth, $sveaOrderId)
    {
        $conf = new SveaMageConfigProvider($auth);
        $sveaObject = WebPay::deliverOrder($conf);

        $order = $payment->getOrder();
        $creditMemo = $payment->getCreditmemo();
        $storeId = $order->getStoreId();
        $countryCode = $order->getBillingAddress()->getCountryId();
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $taxConfig = Mage::getSingleton('tax/config');
        $store = Mage::app()->getStore($storeId);

        foreach ($creditMemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }

            // Default to the item price
            $price = $orderItem->getPrice();
            $priceInclTax = $orderItem->getPriceInclTax();
            $taxPercent = $orderItem->getTaxPercent();
            $name = $item->getName();
            $qty = $item->getQty();

            $parentItem = $orderItem->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $price = $parentItem->getPrice();
                        $priceInclTax = $parentItem->getPriceInclTax();
                        $taxPercent = $parentItem->getTaxPercent();
                        $qty = $parentItem->getQtyRefunded();
                        break;
                    case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                        $taxPercent = $priceInclTax = $price = 0;
                        $name = '- ' . $name;
                        break;
                }
            }

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

            $sveaObject->addOrderRow($orderRow);
        }

        $request = $taxCalculationModel->getRateRequest(
                $order->getShippingAddress(),
                $order->getBillingAddress(),
                null,
                $store);

        // Shipping
        if ($creditMemo->getShippingAmount() > 0) {
            $shippingFee = Item::shippingFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName($order->getShippingDescription());

            // We require shipping tax to be set
            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
            $shippingFee->setVatPercent((int)$rate);

            if ($taxConfig->shippingPriceIncludesTax($storeId)) {
                $shippingFee->setAmountIncVat($creditMemo->getShippingInclTax());
            } else {
                $shippingFee->setAmountExVat($creditMemo->getShippingAmount());
            }

            $sveaObject->addFee($shippingFee);
        }

        // Discount
        if (abs($creditMemo->getDiscountAmount()) > 0) {
            $discountRow = Item::fixedDiscount()
                ->setAmountIncVat(abs($creditMemo->getDiscountAmount()))
                ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($discountRow);
        }

        // Gift cards
        if (abs($creditMemo->getGiftCardsAmount()) > 0) {
            $giftCardRow = Item::fixedDiscount()
                    ->setAmountIncVat(abs($creditMemo->getGiftCardsAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFee = $creditMemo->getSveaPaymentFeeAmount();
        $paymentFeeInclTax = $creditMemo->getSveaPaymentFeeInclTax();
        $refunded = $creditMemo->getOrder()->getSveaPaymentFeeRefunded();
        if ($paymentFee > 0 && $refunded == 0) {
            $invoiceFee = Item::invoiceFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                    ->setAmountExVat($paymentFee)
                    ->setAmountIncVat($paymentFeeInclTax);

            $sveaObject = $sveaObject->addFee($invoiceFee);
            $creditMemo->getOrder()->setSveaPaymentFeeRefunded($paymentFeeInclTax);
        }

        $adjustmentFee = $creditMemo->getAdjustmentPositive();
        if ($adjustmentFee > 0) {
            $invoiceAdjustment = Item::invoiceFee()
                    ->setVatPercent(0)
                    ->setAmountIncVat($adjustmentFee);

            $sveaObject->addFee($invoiceAdjustment);
        }

        $response = $sveaObject->setCountryCode($countryCode)
                ->setOrderId($sveaOrderId)
                ->setInvoiceDistributionType(Mage::getStoreConfig("payment/svea_invoice/deliver_method"));

        $order->setData('svea_refund_request', $response);

        return $order->getData('svea_refund_request');
    }

    /**
     * Returns translated Error message. If not found, returns errorcode and message in english direkt from server.
     * @param type $err
     * @param type $msg
     * @return type
     */
    public function responseCodes($err, $msg = "")
    {
        $definition = Mage::helper('svea_webpay')->__("Error_" . $err, $err);

        if (preg_match("/^Error/", $definition)) {
            $definition = Mage::helper('svea_webpay')->__("Error_error", $err . " : " . $msg);
        }

        return $definition;
    }

    /**
     * Used to determine if we should print the extra price text or not
     *
     * @return bool
     */
    public function shouldDisplayMonthlyFee($block)
    {
        return Mage::getStoreConfig('payment/svea_paymentplan/active')
                && Mage::getStoreConfig('payment/svea_paymentplan/activate_product_price')
                && $block->getIdSuffix() !== '-related'
                && $block->getIdSuffix() !== '_clone'
                && !strstr($block->getTemplate(), 'tierprices');
    }
}