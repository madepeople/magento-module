<?php

/**
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Block_Adminhtml_Sales_Order_Creditmemo_Create_Adjustments
    extends Mage_Adminhtml_Block_Template
{
    protected $_source;

    /**
     * Initialize creditmemo adjustment totals
     *
     * @return Svea_WebPay_Block_Adminhtml_Sales_Order_Creditmemo_Create_Adjustments
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $this->_source  = $parent->getSource();
        $total = new Varien_Object(array(
            'code'      => 'svea_payment_fee_adjustments',
            'block_name'=> $this->getNameInLayout()
        ));
        $parent->removeTotal('svea_payment_fee');
        $parent->addTotal($total);
        return $this;
    }

    public function getSource()
    {
        return $this->_source;
    }

    /**
     * Get the svea shipment fee for possible refund adjustment
     *
     * @return float
     */
    public function getSveaPaymentFeeAmount()
    {
        $config = Mage::getSingleton('tax/config');
        $source = $this->getSource();
        if ($config->displaySalesPricesInclTax($source->getOrder()->getStoreId())) {
            $paymentFee = $source->getBaseSveaPaymentFeeInclTax();
        } else {
            $paymentFee = $source->getBaseSveaPaymentFeeAmount();
        }
        return Mage::app()->getStore()->roundPrice($paymentFee) * 1;
    }

    /**
     * Get label for the payment fee total based on configuration settings
     *
     * @return string
     */
    public function getPaymentFeeLabel()
    {
        $config = Mage::getSingleton('tax/config');
        $source = $this->getSource();
        if ($config->displaySalesPricesInclTax($source->getOrder()->getStoreId())) {
            $label = $this->helper('sales')->__('Refund Svea Invoice Fee (Incl. Tax)');
        } elseif ($config->displaySalesPricesBoth($source->getOrder()->getStoreId())) {
            $label = $this->helper('sales')->__('Refund Svea Invoice Fee (Excl. Tax)');
        } else {
            $label = $this->helper('sales')->__('Refund Svea Invoice Fee');
        }
        return $label;
    }
}
