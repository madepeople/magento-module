<?php
/**
 * This rewrite lets us put the custom "Part payment form XX/month" text with
 * product prices in the product view and list
 */
class Svea_WebPay_Block_Frontend_Catalog_Product_Price
    extends Mage_Bundle_Block_Catalog_Product_Price
{    
    /**
     * Simply append the monthly fee information after the price block
     * 
     * @return string
     */
    protected function _toHtml()
    {
        $html = parent::_toHtml();

        if (Mage::helper('svea_webpay')->shouldDisplayMonthlyFee($this)) {
            $html .= $this->getLayout()
                    ->createBlock('svea_webpay/frontend_catalog_product_price_monthlyfee')
                    ->setProduct($this->getProduct())
                    ->toHtml();
        }
        
        return $html;
    }
}
