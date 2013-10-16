<?php

/**
 * @author jonathan@madepeople.se
 */
abstract class Svea_WebPay_Model_Payment_Abstract
    extends Mage_Payment_Model_Method_Abstract
{
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
     *  Use the simple associated product prices, but we need to know that the
     *  parent of the simple product is actually a bundle product
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
            // Do not include the Bundle as product. Only its products.
            if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                    || $item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
            }

            switch (get_class($item)) {
                case 'Mage_Sales_Model_Order_Item':
                    $qty = $item->getQtyOrdered();
                    break;
                default:
                    $qty = $item->getQty();
                    // We only need the qty from the child item
                    $item = $item->getOrderItem();
                    break;
            }

            // Default to the current item price
            $price = $item->getPrice();
            $priceInclTax = (float)$item->getPriceInclTax();
            $taxPercent = (float)$item->getTaxPercent();

            $parentItem = $item->getParentItem();
            if ($parentItem) {
                switch ($parentItem->getProductType()) {
                    case Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE:
                        $price = (float)$parentItem->getPrice();
                        $priceInclTax = (float)$parentItem->getPriceInclTax();
                        $taxPercent = $parentItem->getTaxPercent();
                        break;
                }
            }

            $row = Item::orderRow();
            $row->setArticleNumber($item->getProductId())
                    ->setQuantity((int)$qty)
                    ->setName($item->getName())
                    ->setDescription($item->getShortDescription())
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

    protected function _addShippingRow($svea, $object)
    {
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
     */
    protected function _addTotals($svea, $object)
    {
        $rootNode = Mage::getConfig()->getNode('global/svea/totals');
        foreach ($rootNode->children() as $node) {
            $node = (string)$node;
            list($model, $method) = explode('::', $node);
            if ($model === 'self') {
                $this->$method($object);
            } else {
                Mage::getModel($model)->$method($object);
            }
        }

        Mage::dispatchEvent('svea_initialize_total_rows', array(
            'svea' => $svea,
            'object' => $object
        ));

        return $this;
    }

    protected function _initializeSveaOrder($svea, $object)
    {
        $data = $this->getInfoInstance()->getData($this->getCode());
        $svea->setCountryCode($data['country']);

        return $this;
    }

    protected function _addPaymentFee($svea, $object)
    {
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