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

        $latestTimestamp = $this->getLatestUpdateOfPaymentPlanParams();

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
    public function getLatestUpdateOfPaymentPlanParams()
    {
        $collection = Mage::getModel('svea_webpay/paymentplan')->getCollection()
                ->setOrder('timestamp', Varien_Data_Collection::SORT_ORDER_DESC);

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
        // Add invoiced items
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            //Do not include the Bundle as product. Only it's products.

            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                continue;
            }
            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }
            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                continue;
            }

            if (!$item->getQty()) {
                continue;
            }

            // Set price amounts in regards to above
            if (($parentItem = $orderItem->getParentItem()) !== null) {
                $price = $parentItem->getPrice();
                $priceInclTax = $parentItem->getPriceInclTax();
            } else {
                $price = $item->getPrice();
                $priceInclTax = $item->getPriceInclTax();
            }

            $orderRow = Item::orderRow()
                    ->setArticleNumber($item->getProductId())
                    ->setQuantity($item->getQty())
                    ->setAmountExVat($price)
                    ->setName($item->getName())
                    ->setDescription($item->getShortDescription())
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountIncVat($priceInclTax);

            $sveaObject->addOrderRow($orderRow);
        }

        // Add shipping fee
        if ($invoice->getShippingAmount() > 0) {
            $shippingIncVat = $invoice->getShippingAmount() + $invoice->getShippingTaxAmount();

            $shippingFee = Item::shippingFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName($invoice->getShippingMethod())
                    ->setDescription($invoice->getShippingDescription())
                    ->setAmountExVat($invoice->getShippingAmount())
                    ->setAmountIncVat($shippingIncVat);

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
        $payment = $invoice->getOrder()->getPayment();
        $paymentFee = $payment->getAdditionalInformation('svea_payment_fee');
        $paymentFeeTaxAmount = $payment->getAdditionalInformation('svea_payment_fee_tax_amount');
        $invoiced = $payment->getAdditionalInformation('svea_payment_fee_invoiced');

        if ($paymentFee > 0 && $invoiced == 0) {
            $invoiceFee = Item::invoiceFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                    ->setAmountExVat($paymentFee - $paymentFeeTaxAmount)
                    ->setAmountIncVat($paymentFee);

            $sveaObject->addFee($invoiceFee);
            $payment->setAdditionalInformation('svea_payment_fee_invoiced', 1);
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
        $countryCode = $order->getBillingAddress()->getCountryId();

        foreach ($creditMemo->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            //Do not include the Bundle as product. Only it's products.
            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                continue;
            }
            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }
            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                continue;
            }

            if (!$item->getQty()) {
                continue;
            }

            if (($parentItem = $orderItem->getParentItem()) !== null) {
                $price = $parentItem->getPrice();
                $priceInclTax = $parentItem->getPriceInclTax();
            } else {
                $price = $item->getPrice();
                $priceInclTax = $item->getPriceInclTax();
            }

            $orderRow = Item::orderRow()
                    ->setArticleNumber($item->getProductId())
                    ->setQuantity($item->getQty())
                    ->setAmountExVat($price)
                    ->setName($item->getName())
                    ->setDescription($item->getShortDescription())
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountIncVat($priceInclTax);

            $sveaObject->addOrderRow($orderRow);

        }

        // Shipping
        if ($creditMemo->getShippingAmount() > 0) {
            $shippingIncVat = $creditMemo->getShippingAmount() + $creditMemo->getShippingTaxAmount();

            $shippingFee = Item::shippingFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName($creditMemo->getShippingMethod())
                    ->setDescription($creditMemo->getShippingDescription())
                    ->setAmountExVat($creditMemo->getShippingAmount())
                    ->setAmountIncVat($shippingIncVat);

            $sveaObject->addFee($shippingFee);
        }

        // Discount
        if (abs($creditMemo->getDiscountAmount()) > 0) {
            $discountRow = Item::fixedDiscount()
                    ->setAmountIncVat(abs($creditMemo->getDiscountAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($discountRow);
        }

        //Gift cards
        if (abs($creditMemo->getGiftCardsAmount()) > 0) {
            $giftCardRow = Item::fixedDiscount()
                    ->setAmountIncVat(abs($creditMemo->getGiftCardsAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFee = $payment->getAdditionalInformation('svea_payment_fee');
        $adjustmentFee = $creditMemo->getAdjustmentPositive();
        $paymentFeeTaxAmount = $payment->getAdditionalInformation('svea_payment_fee_tax_amount');
        $refunded = $payment->getAdditionalInformation('svea_payment_fee_refunded');

        if ($paymentFee > 0 && $refunded == 0 && $paymentFee == $adjustmentFee) {
            $invoiceFee = Item::invoiceFee()
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                    ->setAmountExVat($paymentFee - $paymentFeeTaxAmount)
                    ->setAmountIncVat($paymentFee);

            $sveaObject = $sveaObject->addFee($invoiceFee);
            $payment->setAdditionalInformation('svea_payment_fee_refunded', 1);
        } else if ($adjustmentFee > 0) {
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