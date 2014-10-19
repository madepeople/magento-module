<?php

/**
 * Base Block class for all payment method blocks
 *
 */
abstract class Svea_WebPay_Block_Payment_Abstract extends Mage_Payment_Block_Form
{

    /**
     * Payment method logo code
     *
     * If not set no logo will be used.
     *
     * @string|null
     */
    protected $_logoCode = null;

    /**
     * Get URL to payment method logo
     *
     * @returns string|null
     */
    public function getLogoUrl()
    {

        if ($this->_logoCode !== null) {
            $lang = strtoupper(Mage::helper('svea_webpay')->__('lang_code'));
            return Mage::getBaseUrl('media') . "svea/{$lang}/{$this->_logoCode}.png";
        } else {
            return null;
        }

    }

    protected function _construct()
    {

        if ($logoUrl = $this->getLogoUrl()) {
            $this->setMethodLabelAfterHtml('<div class="svea-payment-logos"><img class="svea-method-logo" src="' . $logoUrl . '"></div>');
        }

        return parent::_construct();
    }

    protected function _prepareLayout()
    {
        $head = $this->getLayout()
            ->getBlock('head');

        if (!empty($head)) {
            $head->addCss('svea/css/checkout.css');
            $head->addJs('svea.js');
        }
    }

    /**
     * Get current payment plan information as HTML
     *
     * @return string
     */
    public function getInfoHtml()
    {
        return nl2br(trim(Mage::getStoreConfig("payment/{$this->getMethodCode()}/paymentplan_info")));
    }

}