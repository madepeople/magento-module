<?php
/**
 * Display the simple and extended part payment monthly fee information
 * if activated in admin. The different display conditions are used in the
 * custom $_template
 */
class Svea_WebPay_Block_Frontend_Catalog_Product_Price_Monthlyfee
    extends Mage_Core_Block_Template
{
    protected $_template = 'svea/catalog/product/price.phtml';

    protected $_productPrice;

    /**
     * Get all current layout handles
     *
     * @return array
     */
    protected function _getLayoutHandles()
    {
        return $this->getLayout()
                ->getUpdate()
                ->getHandles();
    }

    /**
     * Get the product price used for monthly fee calculation
     *
     * @return float
     */
    public function getProductPrice()
    {
        if (is_null($this->_productPrice)) {
            $price = $this->getProduct()->getSpecialPrice()
                    ? : $this->getProduct()->getFinalPrice();

            $this->_productPrice = $this->helper('tax')
                    ->getPrice($this->getProduct(), $price, true);
        }

        return $this->_productPrice;
    }

    /**
     * Get pricewidget data
     *
     * @see Svea_WebPay_Helper_Data::getPricewidgetData() for more information
     *
     * @return array
     */
    public function getPricewidgetData()
    {
        return Mage::helper('svea_webpay')->getPricewidgetData($this->getProductPrice());
    }

    /**
     * Are we rendering on a campaign page?
     *
     * @return bool
     */
    public function isCategoryPage()
    {
        return array_search(array(
                'catalog_category_default',
                'catalog_category_layered'), $this->_getLayoutHandles()) !== false;
    }

    /**
     * Are we rendering on a product page?
     *
     * @return bool
     */
    public function isProductPage()
    {
        return array_search('catalog_product_view', $this->_getLayoutHandles()) !== false;
    }

    /**
     * Are we rendering as part of a product listing? Such as related, cross-
     * sell, best buy, etc
     *
     * @return bool
     */
    public function isProductListing()
    {
        // TODO: Implement me
        return false;
    }

    /**
     * Get URL for the logo that should be displayed
     */
    public function getLogoUrl()
    {

        return Mage::helper('svea_webpay')->getLogoUrl(Svea_WebPay_Helper_Data::LOGO_SIZE_MEDIUM);
    }

}