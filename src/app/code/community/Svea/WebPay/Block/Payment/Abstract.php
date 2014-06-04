<?php

abstract class Svea_WebPay_Block_Payment_Abstract extends Mage_Payment_Block_Form
{
    protected $_logoCode;

    protected function _construct()
    {
        if (!empty($this->_logoCode)) {
            $lang = strtoupper(Mage::helper('svea_webpay')->__('lang_code'));
            $titleImg = Mage::getBaseUrl('media') . 'svea/' . $lang . '/' . $this->_logoCode . '.png';
            $this->setMethodLabelAfterHtml('<div class="svea-payment-logos"><img class="svea-method-logo" src="' . $titleImg . '"></div>');
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
}
