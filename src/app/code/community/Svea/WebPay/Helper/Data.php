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
     * cdn domain for logos
     *
     * @var string
     */
    const LOGO_CND_DOMAIN = 'cdn.svea.com';

    /**
     * Constant for small logo size
     *
     * @var string
     */
    const LOGO_SIZE_SMALL = 'small';

    /**
     * Constant for medium logo size
     *
     * @var string
     */
    const LOGO_SIZE_MEDIUM = 'medium';

    /**
     * Constant for large logo size
     *
     * @var string
     */
    const LOGO_SIZE_LARGE = 'large';

    /**
     * Get the language code used in the paypage, by locale.
     *
     * @param $localeCode
     * @return mixed
     */
    public function getLanguageCode($localeCode)
    {
        $localeLangMapping = array(
            'da_DK' => 'da',
            'de_DE' => 'de',
            'en_US' => 'en',
            'fi_FI' => 'fi',
            'nl_NL' => 'nl',
            'nb_NO' => 'no',
            'nn_NO' => 'no',
            'no_NO' => 'no',
            'sv_SE' => 'sv',
            'fr_FR' => 'fr',
            'it_IT' => 'it',
            'es_ES' => 'es',
        );

        if (isset($localeLangMapping[$localeCode])) {
            $lang = $localeLangMapping[$localeCode];
            return $lang;
        }

        // Integration package fallback
        return $localeCode;
    }

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
        $result = $this->_getAddressesResponseFromResellerRegister($ssn, $countryCode, $conf);
        if ($result !== null) {
            return $result;
        }
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
     * Get addresses from the reseller register
     *
     * @return array|null
     */
    protected function _getAddressesResponseFromResellerRegister($ssn, $countryCode, $conf)
    {

        // The registry only container companies
        if (!$conf['company']) {
            return null;
        }
        $addresses = array();
        foreach (Mage::getModel('svea_webpay/customer_address')
                     ->getCollection()
                     ->addFieldToFilter('orgnr', $ssn)
                     ->addFieldToFilter('country_code', $countryCode)
                 as $address) {
            $addresses[] = $address->getAsGetAddressResponse();
        }
        if (!empty($addresses)) {
            $result = new stdClass();
            $result->customerIdentity = $addresses;
            $result->accepted = true;
            $result->errormessage = '';
            $result->resultcode = 'Accepted';
            return $result;
        } else {
            return null;
        }
    }

    /**
     * List of country codes for countries where the result of calling
     * createOrder will replace any entered address with the result from
     * createOrder.
     *
     * @var array
     */
    private $_createOrderOverwritesAddressCountries = array(
        'SE',
        'EN',
        'FI',
        'NO',
        'DK',
        'DE',
        'NL',
    );


    /**
     * Check if a call to createOrder will overwrite the entered address
     *
     * @param $countryCode string Country code
     *
     * @returns bool
     */
    public function createOrderOverwritesAddressForCountry($countryCode)
    {
        return in_array(strtoupper($countryCode), $this->_createOrderOverwritesAddressCountries);
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
     * Get paymentplans that are valid for a cost
     *
     * The arrays in the result has the following keys:
     *
     * - pricePerMonth: Price per month including notificationFee
     * - contractLength: Number of months the contract will run for
     * - campaignCode: The campaign code
     * - isCampaign: If this is a campaign
     * - description: Translated free text description
     * - paymentPlan: Svea_Model_PaymentPlan
     * - notificationFee: Notification fee
     * - initialFee: Initial fee
     *
     * @param $cost The cost in current currency. The value used will be the ciel:ed value of $cost.
     * @param $storeId The store id
     *
     * @returns array List of arrays.
     */
    public function getPaymentPlansForCost($cost, $storeId=null)
    {
        if ($storeId === null) {
            $storeId = Mage::app()->getStore()->getId();
        }
        $latestTimestamp = $this->getLatestUpdateOfPaymentPlanParams($storeId);

        $paymentPlans = array();
        $paymentPlansAsObjects = new stdClass();
        $paymentPlansAsObjects->campaignCodes = array();

        foreach (Mage::getModel('svea_webpay/paymentplan')->getCollection()->addFieldToFilter('timestamp', $latestTimestamp) as $paymentPlan) {
            // XXX: it _is_ called 'campaincode' without 'g' in the database
            $campaignCode = $paymentPlan->getData('campaincode');
            $paymentPlans[$campaignCode] = $paymentPlan;
            $paymentPlansAsObjects->campaignCodes[$campaignCode] = $paymentPlan->asSveaResponse();
        }

        $validPaymentPlans = array();

        foreach(WebPay::paymentPlanPricePerMonth($cost, $paymentPlansAsObjects)->values as $validCampaign) {
            $campaignCode = $validCampaign['campaignCode'];
            $paymentPlan = $paymentPlans[$campaignCode];

            $validCampaign['paymentPlan'] = $paymentPlan;
            $validCampaign['contractLength'] = $paymentPlan->contractlength;
            $validCampaign['notificationFee'] = $paymentPlan->notificationfee;
            $validCampaign['initialFee'] = $paymentPlan->initialfee;

            // XXX: This rounding was present in previous code, however, I don't
            // know _how_ it should be rounded(UP, DOWN etc).
            $validCampaign['pricePerMonth'] = round($validCampaign['pricePerMonth']);

            $validCampaign['isCampaign'] = $paymentPlan->paymentfreemonths && ($paymentPlan->interestfreemonths == $paymentPlan->paymentfreemonths);

            $validPaymentPlans[] = $validCampaign;
        }

        return $validPaymentPlans;
    }

    /**
     * Get paymentplans that are valid for a specific quote
     *
     * @param $quote The quote
     *
     * @returns array List of arrays, see self::getPaymentPlansForCost()
     */
    public function getPaymentPlansForQuote($quote)
    {

        // TODO: Should we also round total and shipping amount?
        return $this->getPaymentPlansForCost(round($quote->getGrandTotal() - $quote->getShippingAmount()));

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
            $name = $item->getName();
            $price = $orderItem->getBasePrice();
            $priceInclTax = $orderItem->getBasePriceInclTax();
            $taxPercent = $orderItem->getTaxPercent();
            if (!(int)$taxPercent) {
                $taxPercent = false;
            }

            $parentItem = $orderItem->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $price = $parentItem->getBasePrice();
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
                // If it's a bundle item we have to calculate the tax from
                // the including/excluding tax values
                $taxPercent = round(100*(($priceInclTax/$price)-1));
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

            $orderRow = WebPayItem::orderRow()
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
        if ($invoice->getBaseShippingAmount() > 0) {
            $shippingFee = WebPayItem::shippingFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName($order->getShippingDescription());

            // We require shipping tax to be set
            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
            $shippingFee->setVatPercent((int)$rate);

            if ($taxConfig->shippingPriceIncludesTax($storeId)) {
                $shippingFee->setAmountIncVat($invoice->getBaseShippingInclTax());
            } else {
                $shippingFee->setAmountExVat($invoice->getBaseShippingAmount());
            }

            $sveaObject->addFee($shippingFee);
        }

        // Possible discount
        $discount = abs($invoice->getBaseDiscountAmount());
        if ($discount) {
            $discountRow = WebPayItem::fixedDiscount()
                ->setAmountIncVat($discount)
                ->setName(Mage::helper('svea_webpay')->__('discount'))
                ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($discountRow);
        }

        // Gift card(s)
        if (abs($order->getBaseGiftCardsAmount())) {
            $giftCardRow = WebPayItem::fixedDiscount()
                    ->setAmountIncVat(abs($order->getBaseGiftCardsAmount()))
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFee = $invoice->getBaseSveaPaymentFeeAmount();
        
        $paymentFeeInclTax = $invoice->getBaseSveaPaymentFeeInclTax();
        $invoiced = $invoice->getOrder()->getSveaPaymentFeeInvoiced();

        if ($paymentFee > 0 && $invoiced == 0) {
            $invoiceFee = WebPayItem::invoiceFee()
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
            $name = $item->getName();
            $price = $orderItem->getBasePrice();
            $priceInclTax = $orderItem->getBasePriceInclTax();
            $taxPercent = $orderItem->getTaxPercent();
            if (!(int)$taxPercent) {
                $taxPercent = false;
            }

            $qty = $item->getQty();

            $parentItem = $orderItem->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $price = $parentItem->getBasePrice();
                        $priceInclTax = $parentItem->getBasePriceInclTax();
                        $taxPercent = $parentItem->getTaxPercent();
                        $qty = $parentItem->getQtyRefunded();
                        break;
                    case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                        $taxPercent = $priceInclTax = $price = 0;
                        $name = '- ' . $name;
                        break;
                }
            }

            if ($taxPercent === false) {
                // If it's a bundle item we have to calculate the tax from
                // the including/excluding tax values
                $taxPercent = round(100*(($priceInclTax/$price)-1));
            }

            $orderRow = WebPayItem::orderRow()
                ->setArticleNumber($item->getSku())
                ->setQuantity((int)$qty)
                ->setName($name)
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setVatPercent((int)$taxPercent)
                ->setAmountIncVat((float)$priceInclTax);

            $sveaObject->addOrderRow($orderRow);
        }

        $request = $taxCalculationModel->getRateRequest(
                $order->getShippingAddress(),
                $order->getBillingAddress(),
                null,
                $store);

        // Shipping
        if ($creditMemo->getBaseShippingAmount() > 0) {
            $shippingFee = WebPayItem::shippingFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName($order->getShippingDescription());

            // We require shipping tax to be set
            $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
            $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
            $shippingFee->setVatPercent((int)$rate);

            if ($taxConfig->shippingPriceIncludesTax($storeId)) {
                $shippingFee->setAmountIncVat($creditMemo->getBaseShippingInclTax());
            } else {
                $shippingFee->setAmountExVat($creditMemo->getBaseShippingAmount());
            }

            $sveaObject->addFee($shippingFee);
        }

        // Discount
        $discount = abs($creditMemo->getBaseDiscountAmount());
        if ($discount > 0) {
            $discountRow = WebPayItem::fixedDiscount()
                ->setAmountIncVat($discount)
                ->setName(Mage::helper('svea_webpay')->__('discount'))
                ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($discountRow);
        }

        // Gift cards
        if (abs($creditMemo->getBaseGiftCardsAmount()) > 0) {
            $giftCardRow = WebPayItem::fixedDiscount()
                ->setAmountIncVat(abs($creditMemo->getBaseGiftCardsAmount()))
                ->setUnit(Mage::helper('svea_webpay')->__('unit'));

            $sveaObject->addDiscount($giftCardRow);
        }

        // Invoice fee
        $paymentFee = $creditMemo->getSveaPaymentFeeAmount();
        $basePaymentFee = $creditMemo->getBaseSveaPaymentFeeAmount();

        $paymentFeeInclTax = $creditMemo->getSveaPaymentFeeInclTax();
        $basePaymentFeeInclTax = $creditMemo->getBaseSveaPaymentFeeInclTax();
        
        $refunded = $creditMemo->getOrder()->getSveaPaymentFeeRefunded();
        if ($paymentFee > 0 && $refunded == 0) {
            $invoiceFee = WebPayItem::invoiceFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                ->setAmountExVat($basePaymentFee)
                ->setAmountIncVat($basePaymentFeeInclTax);

            $sveaObject = $sveaObject->addFee($invoiceFee);
            $creditMemo->getOrder()->setSveaPaymentFeeRefunded($paymentFeeInclTax);
            $creditMemo->getOrder()->setBaseSveaPaymentFeeRefunded($basePaymentFeeInclTax);
        }

        $adjustmentFee = $creditMemo->getBaseAdjustmentPositive();
        if ($adjustmentFee > 0) {
            $invoiceAdjustment = WebPayItem::invoiceFee()
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

    /**
     * Are we using a quick checkout module, such as onestepcheckout or
     * stream checkout?
     *
     * @deprecated
     *
     * @return bool
     */
    public function usingQuickCheckout()
    {
        return Mage::getStoreConfigFlag('streamcheckout/general/enabled')
            // || anotherCriteria() ...
            ;
    }

    /**
     * Check if the SSN selector should be displayed together with the payment method
     *
     * This is based on a setting in admin, it may be disabled for checkouts that
     * inlines the SSN selector together with the address fields.
     *
     * @return bool
     */
    public function showSsnSelectorInPaymentMethod()
    {
        return Mage::getStoreConfigFlag('payment/svea_general/display_ssn_selector_with_payment_method');
    }

    /**
     * Check if the SSN selector should be displayed regardless of payment method
     *
     * This is based on a setting in admin but will only work if
     * showSsnSelectorInPaymentMethod() is false.
     *
     * @return bool
     */
    public function alwaysDisplaySsnSelector()
    {
        return !$this->showSsnSelectorInPaymentMethod() && Mage::getStoreConfigFlag('payment/svea_general/always_display_ssn_selector');
    }

    /**
     * Check if required address fields should be locked
     *
     * @return bool
     */
    public function lockRequiredFields()
    {
        return Mage::getStoreConfigFlag('payment/svea_general/lock_required_fields');
    }

    /**
     * Get price-widget data for a specific price
     *
     * The price widget displays different partpayment combinations and minimum
     * invoice-payment for a specific price.
     *
     * Returns an array which has at least one key, 'enabled' set.
     * If 'enabled' is FALSE the widget should not be displayed.
     * If 'enabled' is TRUE the widget should be displayed. It will have 'label',
     * 'invoice' and 'paymentplan' set.
     *
     * - The label is what should be displayed in the button.
     * - Both 'paymentplan' and 'invoice' are arrays with 'label' and 'rows' set
     * - Each row will have 'label' and 'cost' set, where cost is a formatted string.
     * Example result:
     *
     * array(
     *     'enabled' => true,
     *     'label' => 'From 10 kr',
     *     'methods' => array(
     *         'paymentplan' => array(
     *             'enabled' => true,
     *             'label' => 'Svea delbetalning',
     *             'rows' => array(
     *                 array(
     *                     'label' => '12 MÅNADER LÅN',
     *                     'cost' => '16 kr/månad',
     *                 ),
     *                 array(
     *                     'label' => '24 MÅNADER LÅN',
     *                     'cost' => '10 kr/månad',
     *                 ),
     *             ),
     *         ),
     *         'invoice' => array(
     *             'label' => 'Svea faktura',
     *             'enabled' => true,
     *             'rows' => array(
     *                 'label' => 'Minimum amount to pay',
     *                 'cost' => '50 kr',
     *             ),
     *         ),
     *     ),
     * );
     *
     * @param $cost float The cost that the widget data should be configured for
     *
     * @return array
     */

    public function getPricewidgetData($cost)
    {
        $helper = Mage::helper('svea_webpay');
        $lowestCost = null;
        $currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();

        $widget = array(
            'enabled' => false,
            'methods' => array(
                'paymentplan' => array(
                    'enabled' => false,
                    'label' => $helper->__('paymentplan_info'),
                    'rows' => array(),
                ),
                'invoice' => array(
                    'enabled' => false,
                    'label' => $helper->__('invoice_info'),
                    'rows' => array(),
                ),
            ),
        );

        if (!$this->paymentplanIsEnabled() && !$this->invoiceIsEnabled()) {
            return $widget;
        }

        $widget['enabled'] = true;

        $pricePerMonthSuffix = "{$currencySymbol}/{$helper->__('month')}";

        foreach ($this->getPaymentPlansForCost($cost) as $paymentPlan) {
            $widget['methods']['paymentplan']['enabled'] = true;

            $monthlyCost = $paymentPlan['pricePerMonth'];
            $widget['methods']['paymentplan']['rows'][] = array(
                'label' => $paymentPlan['paymentPlan']->description,
                'cost' => "{$monthlyCost} {$pricePerMonthSuffix}",
            );

            if ($lowestCost === null || $lowestCost > $monthlyCost) {
                $lowestCost = $monthlyCost;
            }
        }

        $locale = Mage::app()->getLocale()->getLocaleCode();
        $country = substr($locale, strlen($locale) - 2, 2);
        $lowestInvoiceAmount = array(
            'SE' => 50,
            'NO' => 100,
            'DK' => 100,
            'FI' => 10,
            'NL' => 10,
        );

        if (array_key_exists($country, $lowestInvoiceAmount)) {
            $widget['methods']['invoice']['enabled'] = true;
            $minimumCost = ceil(max($cost * 0.03, $lowestInvoiceAmount[$country]));
            $widget['methods']['invoice']['rows'][] = array(
                'label' => $helper->__('Minimum amount to pay'),
                'cost' => "{$minimumCost} {$currencySymbol}",
            );
            if ($lowestCost === null || $lowestCost > $minimumCost) {
                $lowestCost = $minimumCost;
            }
        }

        $widget['label'] = "{$helper->__('From')} {$lowestCost} {$currencySymbol}";

        // Disable if there are no rows
        if (empty($widget['methods']['invoice']['rows']) &&
            empty($widget['methods']['paymentplan']['rows'])) {
            $widget['enabled'] = false;
        }

        return $widget;
    }

    /**
     * Check if paymentplan is enabled
     *
     * @return bool
     */
    public function paymentplanIsEnabled()
    {
        return Mage::getStoreConfig('payment/svea_paymentplan/active') === '1';
    }

    /**
     * Check if invoice is enabled
     *
     * @return bool
     */
    public function invoiceIsEnabled()
    {
        return Mage::getStoreConfig('payment/svea_invoice/active') === '1';
    }

    /**
     * Get URL to the logo that should be used
     *
     * If the session has a quote with a valid shipping address country id that
     * country will be used to find out which logo should be used.
     *
     * If there is no valid shipping address country.
     *
     * @throws Mage_Exception If $size isn't a valid size
     *
     * @param size One of then self::LOGO_SIZE_ constants
     *
     * @returns string
     */
    public function getLogoUrl($size=self::LOGO_SIZE_SMALL)
    {

        if (!in_array($size, array(self::LOGO_SIZE_SMALL,
                                   self::LOGO_SIZE_MEDIUM,
                                   self::LOGO_SIZE_LARGE))) {
            throw new Mage_Exception("Not a valid logo size");
        }

        $color = trim(Mage::getStoreConfig('payment/svea_general/logo_color'));
        if (!in_array($color, array('rgb', 'bw', 'bw-neg'))) {
            $color = "rgb";
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if ($quote) {
            $countryCode = $quote->getShippingAddress()->getCountryId();
        }

        switch (strtoupper($countryCode)) {
            case 'SE':
            case 'FI':
            case 'DE':
                $type = 'ekonomi';
                break;
            case 'NO':
            case 'DK':
            case 'NL':
                $type = 'finans';
                break;
            default:
                $type = trim(Mage::getStoreConfig('payment/svea_general/default_logo'));
                if (!$type) {
                    $type = 'ekonomi';
                }
                break;
        }

        if (!in_array($type, array('ekonomi', 'finans'))) {
            $type = 'ekonomi';
        }

        switch ($type) {
        case 'ekonomi':
            $path = "/sveaekonomi/{$color}_ekonomi_{$size}.png";
            break;
        case 'finans':
            $path = "/sveafinans/{$color}_svea-finans_{$size}.png";
            break;
        }

        return "//" . self::LOGO_CND_DOMAIN . $path;
    }

    /**
     * Check if the shipping address may be supplied by the customer
     *
     * @return bool
     */
    public function allowCustomShippingAddress()
    {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
        } else {
            $customer = null;
        }
        $status = new Varien_Object(array(
            'allowCustomShippingAddress' => false,
        ));
        Mage::dispatchEvent('svea_allow_custom_shipping_address', array(
            'customer' => $customer,
            'status' => $status
        ));
        return $status->getData('allowCustomShippingAddress');
    }
}