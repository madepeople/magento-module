<?php

/**
 * Base Block class for all payment method blocks
 *
 */
abstract class Svea_WebPay_Block_Payment_Abstract extends Mage_Payment_Block_Form
{

    protected $_hasLogo = false;

    /**
     * Get URL to payment method logo
     *
     * @returns string|null
     */
    public function getLogoUrl()
    {
        if ($this->_hasLogo) {
            return Mage::helper('svea_webpay')->getLogoUrl(Svea_Webpay_Helper_Data::LOGO_SIZE_SMALL);
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