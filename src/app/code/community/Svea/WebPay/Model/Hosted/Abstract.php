<?php
/**
 * Main class for Hosted payments as Card and Direct payment
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
abstract class Svea_WebPay_Model_Hosted_Abstract extends Svea_WebPay_Model_Abstract
{
    protected $_canReviewPayment = true;
    protected $_formBlockType = 'svea_webpay/payment_hosted';
    protected $_isGateway = true;

    /**
     * There is a chance that Magento rounds prices differently from the
     * integration package, resulting in the small difference in the final
     * total amount. We handle this right now by simply adding an extra row
     * for the difference, throwing an exception if it exceeds 1000 (, in which case
     * we probably need to add a special case for a 3rd party module.
     *
     * @param type $order
     * @param type $additionalInfo
     */
    public function getSveaPaymentObject($order, $additionalInfo = null)
    {
        $svea = parent::getSveaPaymentObject($order, $additionalInfo);

        $grandTotal = $order->getGrandTotal();
        $formatter = new Svea\HostedRowFormatter();
        $rows = $formatter->formatRows($svea);
        $sveaGrandTotal = $formatter->formatTotalAmount($rows);

        $diff = ((int)bcmul($grandTotal, 100))-$sveaGrandTotal;
        if ($diff) {
            if (abs($diff) > 1000) {
                Mage::throwException('The difference between the amount calculated at Svea WebPay and Magento exceeds 10. This must be a bug, please contact support');
            }

            $diff = $diff/100;

            $differenceRow = Item::orderRow()
                    ->setArticleNumber('magento_difference')
                    ->setQuantity(1)
                    ->setName('Magento <-> Svea rounding difference')
                    ->setDescription('A workaround for the possible rounding difference due to tax calculation')
                    ->setUnit(Mage::helper('svea_webpay')->__('unit'))
                    ->setAmountExVat((float)$diff)
                    ->setVatPercent(1) // Bogus placeholder
                    ;

            $svea->addOrderRow($differenceRow);
        }

        return $svea;
    }

    /**
     * Attempt to accept a payment that is under review
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);
        return true;
    }

    /**
     *
     * @return type url
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl($this->_sveaUrl);
    }

    abstract protected function _choosePayment($sveaObject, $addressSelector = NULL);

    /**
     *
     * @return type Svea Payment form object
     */
    public function getSveaPaymentForm()
    {
        $paymentInfo = $this->getInfoInstance();
        $paymentMethodConfig = $this->getSveaStoreConfClass();

        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $order = $paymentInfo->getOrder();
        } else {
            $order = $paymentInfo->getQuote();
        }

        Mage::helper('svea_webpay')->getPaymentRequest($order, $paymentMethodConfig);
        $sveaRequest = $this->getSveaPaymentObject($order);

        $sveaRequest = $this->_choosePayment($sveaRequest);
        return $sveaRequest;
    }

    /**
     * Validate thru Svea integrationLib only if this is an order
     * @return boolean
     */
    public function validate()
    {
        $paymentInfo = $this->getInfoInstance();

        // If quote, skip validation
        if ($paymentInfo instanceof Mage_Sales_Model_Quote_Payment) {
            return true;
        }

        return parent::validate();
    }
}