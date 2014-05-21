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

    /**
     * Returns a constant string defining the type of checkout module used,
     * which we can use in the javascript to determine how to handle markup
     *
     * @return string
     */
    protected function _getCheckoutType()
    {
        $request = $this->getRequest();
        switch ($request->getModuleName()) {
            case 'checkout':
                // Do an extra check here for the Ecomdev module, the standard
                // checkout, as well as the multishipping checkout
                return 'onepage';
            case 'streamcheckout':
                return 'streamcheckout';
            case 'onestepcheckout':
                return 'onestepcheckout';
            case 'firecheckout':
                return 'firecheckout';
        }
    }

    /**
     * This one lets us get arbitrary values stored on the payment method object
     * such as SSN, customer type, VAT number etc
     *
     * @param $key  Additional data key
     * @return string  The value or an empty string
     */
    public function getAdditionalData($key, $type = null)
    {
        $method = $this->getMethod();
        $infoInstance = $method->getInfoInstance();
        $methodInfo = $infoInstance->getAdditionalInformation($method->getCode());
        if (null !== $type) {
            $methodInfo = isset($methodInfo[$type])
                ? $methodInfo[$type] : array();
        }
        return isset($methodInfo[$key])
            ? $methodInfo[$key] : '';
    }

    /**
     * We need the country in templates to make decisions about addresses and
     * stuff
     *
     * @return string
     */
    public function getCountry()
    {
        $method = $this->getMethod();
        $paymentInfo = $method->getInfoInstance();

        return ($paymentInfo instanceof Mage_Sales_Model_Order_Payment)
            ? $paymentInfo->getOrder()->getBillingAddress()->getCountryId()
            : $paymentInfo->getQuote()->getBillingAddress()->getCountryId();
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

        $parameters = Mage::helper('core')->jsonEncode(array(
            'baseUrl' => Mage::getUrl('', array(
                    '_secure' => true
                )),
            'checkoutType' => $this->_getCheckoutType(),
        ));

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

        var callback = function () {
            window.svea = new Svea($parameters);
        }

        // Then bind the event to the callback function.
        // There are several events for cross browser compatibility.
        script.onreadystatechange = callback;
        script.onload = callback;

        // Fire the loading
        head.appendChild(script);
    } else {
        window.svea.displayCountrySpecificFields();
    }
})();
</script>
EOF;

        return $html;
    }
}
