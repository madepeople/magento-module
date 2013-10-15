<?php

/**
 * @author jonathan@madepeople.se
 */
abstract class Svea_WebPay_Block_Payment_Abstract
    extends Mage_Payment_Block_Form
{
    /**
     * It's always a good idea to have the customer type in templates
     *
     * @return string
     */
    public function getCustomerType()
    {
        $method = $this->getMethod();
        $paymentInfo = $method->getInfoInstance();

        $company = ($paymentInfo instanceof Mage_Sales_Model_Order_Payment)
                ? trim($paymentInfo->getOrder()->getBillingAddress()->getCompany())
                : trim($paymentInfo->getQuote()->getBillingAddress()->getCompany());

        return (empty($company))
                ? Svea_WebPay_Helper_Data::TYPE_INDIVIDUAL
                : Svea_WebPay_Helper_Data::TYPE_COMPANY;
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
        ));

        $html = parent::_toHtml();
        $html .= <<<EOF
<script type="text/javascript">
var _sveaLoaded, svea;
(function () {
    if (!_sveaLoaded) {
        // Set this in the beginning because, well, concurrency and stuff
        _sveaLoaded = true;

        var head = document.getElementsByTagName('head')[0];
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = $scriptUrl;

        var callback = function () {
            svea = new Svea($parameters);
        }

        // Then bind the event to the callback function.
        // There are several events for cross browser compatibility.
        script.onreadystatechange = callback;
        script.onload = callback;

        // Fire the loading
        head.appendChild(script);
    }
})();
</script>
EOF;

        return $html;
    }
}