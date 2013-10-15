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
     * @return Svea\CreateOrder
     */
    protected function _initializeSveaOrder($order)
    {
        $sveaConfig = $this->_getSveaConfig();
        $svea = WebPay::createOrder($sveaConfig);
        $storeId = $order->getStoreId();

        foreach ($order->getAllItems() as $item) {
            // Do not include the Bundle as product. Only its products.
            if ($item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                    || $item->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                continue;
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

            $qty = get_class($item) === 'Mage_Sales_Model_Quote_Item'
                    ? $item->getQty()
                    : $item->getQtyOrdered();

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

        // We send tax percentages to Svea because it seems the most reliable
        // way of handling amounts
        $store = Mage::app()->getStore($storeId);
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $request = $taxCalculationModel->getRateRequest(
                $order->getShippingAddress(),
                $order->getBillingAddress(),
                null,
                $store);

        // Shipping, giftcards and discounts needs to be separate rows, use the
        // quote totals to determine what to print and exclude values that
        // are already included from other places
        $quoteId = $order->getQuoteId();
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $quote->collectTotals();

        $totalsToExclude = array('grand_total', 'subtotal', 'tax', 'klarna_tax');

        foreach ($quote->getTotals() as $code => $total) {
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
                            ->setAmountIncVat(abs($order->getDiscountAmount()));

                    $svea->addDiscount($discountRow);
                    break;
                case 'shipping':
                    // We have to somehow make sure that we use the correctly
                    // calculated value, we can't rely on the shipping tax
                    // being part of the quote totals
                    $shippingFee = Item::shippingFee()
                            ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                            ->setName($order->getShippingMethod())
                            ->setDescription($order->getShippingDescription())
                            ->setAmountExVat((float)$order->getShippingAmount());

                    $shippingTaxClass = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $storeId);
                    $rate = $taxCalculationModel->getRate($request->setProductClassId($shippingTaxClass));
                    if (empty($rate)) {
                        throw new Mage_Payment_Exception('The shipping fee needs a tax rate for Svea Invoice to work.');
                    }
                    $shippingFee->setVatPercent((int)$rate);

                    $svea->addFee($shippingFee);
                    break;
//                case 'svea_invoice_fee':
//                    // The payment fee should be fetched from the totals, not
//                    // additional_information
//                    $paymentFee = $order->getPayment()->getAdditionalInformation('svea_payment_fee');
//                    $paymentFeeTaxAmount = $order->getPayment()->getAdditionalInformation('svea_payment_fee_tax_amount');
//
//                    $invoiceFeeRow = Item::invoiceFee()
//                            ->setUnit(Mage::helper('svea_webpay')->__('unit'))
//                            ->setName(Mage::helper('svea_webpay')->__('invoice_fee'))
//                            ->setAmountExVat($paymentFee - $paymentFeeTaxAmount)
//                            ->setAmountIncVat($paymentFee);
//
//                    $svea->addFee($invoiceFeeRow);
//                    break;
                default:
                    Mage::log($total->getCode() . ' is currently not handled');
                    Mage::dispatchEvent('svea_initialize_total_row', array(
                        'svea' => $svea,
                        'total' => $svea
                    ));
                    break;
            }
        }

        $createdAt = date('Y-m-d', strtotime($order->getCreatedAt()));
        $data = $this->getInfoInstance()->getData($this->getCode());
        $svea->setCountryCode($data['country'])
                ->setClientOrderNumber($order->getIncrementId())
                ->setOrderDate($createdAt)
                ->setCurrency($order->getOrderCurrencyCode());

        return $svea;
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