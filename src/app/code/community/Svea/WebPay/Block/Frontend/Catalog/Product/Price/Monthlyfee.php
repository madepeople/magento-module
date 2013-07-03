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
    
    protected $_campaignCollection;
    protected $_productPrice;
    
    /**
     * Get the campaigns valid for the current product. Does it make sense to
     * cache this differently in large product listings? Perhaps fetch it all
     * from the database and iterate through an array
     * 
     * @return Varien_Data_Collection_Db
     */
    protected function _getCampaignCollection()
    {
        if (is_null($this->_campaignCollection)) {
            $collection = Mage::getModel('svea_webpay/paymentplan')
                    ->getCollection();
            
            $price = Mage::helper('core')->currency($this->getProductPrice(), false, false);
            
            $collection->getSelect()
                    ->where('fromamount < ?', $price)
                    ->orWhere('toamount > ?', $price)
                    ->order('monthlyannuityfactor', Varien_Data_Collection::SORT_ORDER_ASC);
            
            $this->_campaignCollection = $collection;
        }

        return $this->_campaignCollection;
    }
    
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
     * Public campaigns getter
     * 
     * @return Varien_Data_Collection_Db
     */
    public function getCampaigns()
    {
        return $this->_getCampaignCollection();
    }
    
    /**
     * Get the cheapest campaign valid for the current product
     * 
     * @return bool|Varien_Object
     */
    public function getCheapestCampaign()
    {
        $collection = $this->_getCampaignCollection();
        if (!$collection->count()) {
            return false;
        }
        
        return $collection->getIterator()
                ->current();
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
     * The text used to display monthly fee campaign information
     * 
     * @param float $pricePerMonth
     * @return string
     */
    public function getCampaignString($campaign)
    {
        $formattedPrice = Mage::helper('core')->currency($this->getProductPrice(), false, false);
        $monthlyAmount = $campaign->getMonthlyannuityfactor() * $formattedPrice;
        $currentCurrency =  Mage::app()->getStore($storeID)->getCurrentCurrencyCode();
        
        $finalPricePerMonth = ($currentCurrency == "EUR") ? number_format($monthlyAmount + $campaign->getNotificationfee(), 2) : number_format($monthlyAmount + $campaign->getNotificationfee(), 0);
        
        return Mage::helper('svea_webpay')->__('from_about')
                . ' ' . $finalPricePerMonth
                . ' ' . Mage::app()->getStore()->getCurrentCurrencyCode()
                . '/' . Mage::helper('svea_webpay')->__('month');
    }
}