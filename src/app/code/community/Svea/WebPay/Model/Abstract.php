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

    /**
     * There is no point of having the svea methods available when
     * grand total is 0
     */
    public function isAvailable($quote = null)
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }

        $grandTotal = (float)$quote->getGrandTotal();
        if ($grandTotal === 0.0) {
            return false;
        }

        return true;
    }

    public function isTest()
    {
        return $this->getConfigData('test');
    }

    /**
     * Returns the main tax percentage of the order
     *
     * @param $order
     * @return int
     */
    protected function _getOrderMainTaxRate($order)
    {
        $appliedTaxes = $order->getAppliedTaxes();
        if (null === $appliedTaxes) {
            $tax = Mage::getModel('sales/order_tax')->load($order->getId(), 'order_id');
            $rate = $tax->getPercent();
        } else {
            $orderTax = array_shift($appliedTaxes);
            $rate = $orderTax['percent'];
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
        //Get Request and billing addres
        $svea = $order->getData('svea_payment_request');
        $billingAddress = $order->getBillingAddress();
        $storeId = $order->getStoreId();
        $store = Mage::app()->getStore($storeId);
        $taxCalculationModel = Mage::getSingleton('tax/calculation');

        // Build the rows for request
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }

            // Default to the item price
            $name = $item->getName();
            $price = $item->getBasePrice();

            $priceInclTax = $item->getBasePriceInclTax();
            $taxPercent = $item->getTaxPercent();
            if (!(int)$taxPercent) {
                $taxPercent = false;
            }

            $parentItem = $item->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $priceInclTax = $parentItem->getBasePriceInclTax();
                        $taxPercent = $parentItem->getTaxPercent();
                        break;
                    case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                        $taxPercent = $priceInclTax = $price = 0;
                        $name = '- ' . $name;
                        break;
                }
            }

            if ($taxPercent === false) {
                if ((float)$price === 0.0) {
                    if ((float)$priceInclTax === 0.0) {
                        $taxPercent = 0;
                    } else {
                        throw new Mage_Exception("Price is 0 but priceIncTax is not");
                    }
                } else {
                    // If it's a bundle item we have to calculate the tax from
                    // the including/excluding tax values
                    $taxPercent = round(100*(($priceInclTax/$price)-1));
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

        // Shipping
        if ($order->getBaseShippingAmount() > 0) {
            $shippingFee = WebPayItem::shippingFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName($order->getShippingDescription());

            // We require shipping tax to be set
            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
            $shippingFee->setVatPercent((int)$rate);
            $shippingFee->setAmountIncVat($order->getBaseShippingInclTax());
            $svea->addFee($shippingFee);
        }

        // Discount
        $discount = abs($order->getBaseDiscountAmount());
        if ($discount > 0) {
            $discountRow = WebPayItem::fixedDiscount()
                ->setName(Mage::helper('svea_webpay')->__('discount'))
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setAmountIncVat($discount);

            $svea->addDiscount($discountRow);
        }

        // Gift cards
        if (abs($order->getBaseGiftCardsAmount()) > 0) {
            $giftCardRow = WebPayItem::fixedDiscount()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setAmountIncVat(abs($order->getGiftCardsAmount()));

            $svea->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFeeInclTax = $order->getBaseSveaPaymentFeeInclTax();
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
            ->setCurrency($order->getBaseCurrencyCode());

        return $svea;
    }

    /**
     * Use Svea IntegrationLib validation to get Exceptions
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
        $additionalInformation = $paymentInfo->getAdditionalInformation();

        if (empty($additionalInformation) || !isset($additionalInformation['svea_customerType'])) {
            $paymentData = $paymentInfo->getData();
            $code = isset($paymentData[$this->getCode()])
                  ? $this->getCode() : 'svea_info';

            if (isset($paymentData[$code])) {
                $additionalInformation = $paymentData[$this->getCode()];
            } else {
                $additionalInformation = array();
            }
        }

        // Add _POST data to additionalInformation if it's not already set in
        // payment.
        // This is because some countries like Finland doesn't make a getAddress()
        // call so for onepage checkouts the svea_info has to be taken from _POST
        // _but_ for multi-page checkouts that information has already been posted
        $postSveaInfo = @$_POST['payment'][@$_POST['payment']['method']];
        if (is_array($postSveaInfo)) {
            foreach ($postSveaInfo as $key => $value) {
                if (strpos($key, 'svea_') === 0) {
                    if (!array_key_exists($key, $additionalInformation)) {
                        $additionalInformation[$key] = $value;
                    }
                }
            }
        }

        $paymentInfo->setAdditionalInformation($additionalInformation);
        // Save the information in database
        $resource = Mage::getSingleton('core/resource');
        $tableName = $resource->getTableName('sales_flat_quote_payment');
        $connection = $resource->getConnection('core_write');
        $connection->query("UPDATE {$tableName} SET additional_information=:data WHERE payment_id=:paymentId LIMIT 1",
                           array(
                               'data' => serialize($additionalInformation),
                               'paymentId' => $paymentInfo->getId(),
                           ));

        $sveaRequest = $this->getSveaPaymentObject($order, $additionalInformation);
        $sveaRequest = $this->_choosePayment($sveaRequest);
        $errors = $sveaRequest->validateOrder();
        if (count($errors) > 0) {
            $exceptionString = "";
            foreach ($errors as $key => $value) {
                $exceptionString .="-" . $key . " : " . $value . "\n";
            }
            Mage::throwException($this->_getHelper()->__($exceptionString));
        }

        // TODO: Should we save the new values here since we are validating them?
        return parent::validate();
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
