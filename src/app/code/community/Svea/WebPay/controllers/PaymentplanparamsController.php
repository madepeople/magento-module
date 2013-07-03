<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

/**
 * Class for Updating PaymentPlan params to table
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_PaymentplanparamsController extends Mage_Adminhtml_Controller_Action
{

    /**
     * This function is called via AJAX from the Payment Methods interface
     * for part payments and is used to update the payment plans stored in the
     * database
     *
     * @return void
     */
    public function checkAction()
    {
        try {
            $result = Mage::helper('svea_webpay')->updatePaymentPlanParams(
                    $this->getRequest()->getParam('id', null),
                    $this->getRequest()->getParam('scope', null));
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}