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
            'code'      => 'svea_fee_adjustments',
            'block_name'=> $this->getNameInLayout()
        ));
        $parent->addTotal($total);
        return $this;
    }

    public function getSource()
    {
        return $this->_source;
    }
}
