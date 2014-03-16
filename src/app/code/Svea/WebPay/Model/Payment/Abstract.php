<?php

/**
 * @author jonathan@madepeople.se
 */
abstract class Svea_WebPay_Model_Payment_Abstract
    extends Mage_Payment_Model_Method_Abstract
{
    /**
     * This saves our extra custom data in the database so we can re-use it
     * when the customer for instance enters a SSN and then goes away and back
     * to the checkout. Why isn't this the Magento default?
     *
     * @param Varien_Object $data
     * @return \Svea_WebPay_Model_Payment_Abstract
     */
    public function assignData($data)
    {
        if (is_object($data)) {
            $data = $data->toArray();
        }
        if (!empty($data) && isset($data[$this->_code])) {
            $this->getInfoInstance()->setAdditionalInformation($this->_code,
                    $data[$this->_code]);
        }
        return parent::assignData($data);
    }

    /**
     * Svea integration package config getter
     *
     * @return \Svea_WebPay_Svea_MagentoProvider
     */
    protected function _getSveaConfig()
    {
        return new Svea_WebPay_Svea_MagentoProvider($this);
    }

    /**
     * Used internally to validate the sum o the Svea rows. The point is that
     * if the two amounts differ, we can't guarantee that activations, refunds,
     * voids, etc will actually work.
     *
     * @param Svea\CreateOrder $svea
     * @param float $amount
     */
    protected function _validateAmount($svea, $amount)
    {
        $diff = $this->getAmountDifference($svea, $amount);
        if ($diff) {
            throw new Mage_Payment_Exception('The by Svea calculated grand total differs from Magento by ' . ($diff/100) . '. This is most likely caused by a bug or misconfiguration.');
        }
        return true;
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
     * @param Svea\CreateOrder $svea
     * @param object $object  An order, Invoice, Creditmemo, etc
     * @return Svea\CreateOrder
     */
    protected function _addItems($svea, $object)
    {
        $storeId = $object->getStoreId();

        foreach ($object->getAllItems() as $item) {
            if ($item instanceof Mage_Sales_Model_Order_Item) {
                $orderItem = $item;
            } else {
                $orderItem = $item->getOrderItem();
            }

            if ($orderItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                // The configurable item is only interesting as a parent
                continue;
            }

            switch (get_class($item)) {
                case 'Mage_Sales_Model_Order_Item':
                    $qty = $item->getQtyOrdered();
                    break;
                default:
                    $qty = $item->getQty();
                    break;
            }

            // Default to the current item price
            $price = $orderItem->getPrice();
            $priceInclTax = (float)$orderItem->getPriceInclTax();
            $taxPercent = (float)$orderItem->getTaxPercent();
            $name = $item->getName();

            $parentItem = $orderItem->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $price = (float)$parentItem->getPrice();
                        $priceInclTax = (float)$parentItem->getPriceInclTax();
                        $taxPercent = $parentItem->getTaxPercent();
                        break;
                    case Mage_Catalog_Model_Product_Type::TYPE_BUNDLE:
                        $taxPercent = $priceInclTax = $price = 0;
                        $name = '- ' . $name;
                        break;
                }
            }

            $row = Item::orderRow();
            $row->setArticleNumber($item->getSku())
                    ->setQuantity((int)$qty)
                    ->setName($name)
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setVatPercent((int)$taxPercent);

            if (Mage::getStoreConfig('tax/calculation/price_includes_tax', $storeId)) {
                $row->setAmountIncVat($priceInclTax);
            } else {
                $row->setAmountExVat($price);
            }

            $svea->addOrderRow($row);
        }

        return $this;
    }

    /**
     * In the case of a quote we add the different rows to the svea object
     * from the quote totals. It sucks a bit that the order object isn't
     * structured in this way as well
     *
     * @param object $svea
     * @param object $object  Quote
     * @return \Svea_WebPay_Model_Payment_Abstract
     */
    protected function _addTotalsFromQuote($svea, $object)
    {
        $quoteId = $object->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->collectTotals();

        $totals = $quote->getTotals();
        $totalsToExclude = array('grand_total', 'subtotal', 'tax',
            'klarna_tax', 'svea_invoice_fee');

        foreach ($totals as $code => $total) {
            if (in_array($code, $totalsToExclude)) {
                continue;
            }

            switch ($code) {
                case 'discount':
                case 'giftcardaccount':
                case 'ugiftcert':
                    $discountRow = Item::fixedDiscount()
                            ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                            ->setName($total->getTitle())
                            ->setAmountIncVat(abs($object->getDiscountAmount()));

                    $svea->addDiscount($discountRow);
                    break;
                case 'shipping':
                    // We have to somehow make sure that we use the correctly
                    // calculated value, we can't rely on the shipping tax
                    // being part of the quote totals
                    break;
                default:
                    Mage::log($total->getCode() . ' is currently not handled in Svea _addTotals()');
                    Mage::dispatchEvent('svea_initialize_total_row', array(
                        'svea' => $svea,
                        'total' => $svea
                    ));
                    break;
            }
        }

        return $this;
    }

    /**
     * Adds the invoice fee row to the Svea object
     *
     * @param object $svea
     * @param object $object  Order or quote
     * @param string $code
     * @return void
     */
    protected function _addInvoiceFeeRow($svea, $object, $code = null)
    {
        $value = $object->getData($code);
        if (empty($value)) {
            return;
        }

        if (!($object instanceof Mage_Sales_Model_Order)) {
            $object = $object->getOrder();
        }

        // The payment fee should be fetched from the totals, not
        // additional_information
        $paymentFee = $object->getPayment()->getAdditionalInformation('svea_payment_fee');
        $paymentFeeTaxAmount = $object->getPayment()->getAdditionalInformation('svea_payment_fee_tax_amount');

        $invoiceFeeRow = Item::invoiceFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
                ->setAmountExVat($paymentFee - $paymentFeeTaxAmount)
                ->setAmountIncVat($paymentFee);

        $svea->addFee($invoiceFeeRow);
    }

    /**
     * Adds a discount row to the svea object
     *
     * @param object $svea
     * @param object $object  Order or quote
     * @param string $code
     * @return void
     */
    protected function _addDiscountRow($svea, $object, $code = null)
    {
        $value = $object->getData($code);
        if (empty($value)) {
            return;
        }

        $discountRow = Item::fixedDiscount()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName(Mage::helper('svea_webpay')->__('Discount'))
                ->setAmountIncVat(abs($object->getDiscountAmount()));

        $svea->addDiscount($discountRow);
    }

    /**
     * Adds the shipping information to the Svea order object
     *
     * @param object $svea
     * @param object $object  Order or quote
     * @param string $code
     * @return void
     * @throws Mage_Payment_Exception
     */
    protected function _addShippingRow($svea, $object, $code = null)
    {
        $value = $object->getShippingAmount();
        if (empty($value)) {
            return;
        }

        // We send tax percentages to Svea because it seems the most reliable
        // way of handling amounts
        $storeId = $object->getStoreId();
        $store = Mage::app()->getStore($storeId);
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $request = $taxCalculationModel->getRateRequest(
                $object->getShippingAddress(),
                $object->getBillingAddress(),
                null,
                $store);

        $shippingFee = Item::shippingFee()
                ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                ->setName($object->getShippingMethod())
                ->setDescription($object->getShippingDescription())
                ->setAmountExVat((float)$object->getShippingAmount());

        $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
        $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
        if (empty($rate)) {
            throw new Mage_Payment_Exception('The shipping fee needs a tax rate for Svea Invoice to work.');
        }
        $shippingFee->setVatPercent((int)$rate);
        $svea->addFee($shippingFee);
    }

    /**
     * Add totals values (rows) to the Svea object
     *
     * @param object $svea
     * @param object $object  Order or quote
     * @return \Svea_WebPay_Model_Payment_Abstract
     */
    protected function _addTotals($svea, $object)
    {
        $rootNode = Mage::getConfig()->getNode('global/svea/totals');
        foreach ($rootNode->children() as $node) {
            $node = (string)$node;
            list($model, $method) = explode('::', $node);
            if ($model === 'self') {
                $this->$method($svea, $object, "$node");
            } else {
                Mage::getModel($model)->$method($svea, $object, "$node");
            }
        }

        Mage::dispatchEvent('svea_initialize_total_rows', array(
            'svea' => $svea,
            'object' => $object
        ));

        return $this;
    }

    /**
     * Initialize the Svea order object
     *
     * @param object $svea
     * @param object $object
     * @return \Svea_WebPay_Model_Payment_Abstract
     */
    protected function _initializeSveaOrder($svea, $object)
    {
        $countryCode = $this->getInfoInstance()
            ->getOrder()
            ->getBillingAddress()
            ->getCountryId();
        $svea->setCountryCode($countryCode);
        return $this;
    }

    public function getAmountDifference($svea, $amount)
    {
        if ($this instanceof Svea_WebPay_Model_Payment_Service_Abstract) {
            // @TODO - Implement something that can handle these kinds of
            // differences, it's not entirely super epic that the two different
            // row formatters have completely different interfaces
            return 0; // Assume no difference, lol
        } else {
            $formatter = new Svea\HostedRowFormatter();
            $rows = $formatter->formatRows($svea);
            $sveaGrandTotal = $formatter->formatTotalAmount($rows);
        }

        return ((int)bcmul($amount, 100))-$sveaGrandTotal;
    }
}