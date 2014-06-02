<?php

abstract class Svea_WebPay_Block_Payment_Abstract extends Mage_Payment_Block_Form
{

    protected $_logoCode;

    protected function _construct()
    {
        if (!empty($this->_logoCode)) {
            $lang = strtoupper(Mage::helper('svea_webpay')->__('lang_code'));
            $titleImg = Mage::getBaseUrl('media') . 'svea/' . $lang . '/' . $this->_logoCode . '.png';
            $this->setMethodLabelAfterHtml('<img class="svea-method-logo" src="' . $titleImg . '">');
        }

        return parent::_construct();
    }

    protected function _prepareLayout()
    {
        $head = $this->getLayout()
            ->getBlock('head');

        $head->addCss('svea/css/checkout.css');
    }

    /**
     * Loads the svea.js file and instantiates the svea payment object
     *
     * @return string
     */
    protected function _toHtml()
    {
        $scriptUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS,
                true) . 'svea.js';
        $scriptUrl = Mage::helper('core')->jsonEncode($scriptUrl);

        $html = parent::_toHtml();
        $html .= <<<EOF
<script type="text/javascript">
var svea;
window.svea = svea;
(function () {
    if (!window.svea) {
        // Set this in the beginning because, well, concurrency and stuff
        window.svea = true;

        var head = document.getElementsByTagName('head')[0];
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = $scriptUrl;

        // Fire the loading
        head.appendChild(script);
    }
})();
</script>
EOF;

        return $html;
    }
}
