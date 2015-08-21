<?php

/**
 * Payment plan information block used in the checkout process
 */
class Svea_WebPay_Block_Payment_Service_PaymentPlan extends Svea_WebPay_Block_Payment_Service_Abstract
{

    protected $_template = 'svea/payment/service/paymentplan.phtml';
    protected $_hasLogo = true;

    /**
     * Get list of paymentplans that are valid for the current quote
     *
     * @returns array See Svea_Webpay_Helper_data::getPaymentPlansForQuote()
     */
    public function getPaymentPlans()
    {
        return Mage::helper('svea_webpay')->getPaymentPlansForQuote(Mage::getSingleton('checkout/session')->getQuote());
    }

}
