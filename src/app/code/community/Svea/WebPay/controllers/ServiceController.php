<?php
/**
 * Class to getAddress
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_ServiceController extends Mage_Core_Controller_Front_Action
{

    public function getAddressesAction()
    {
        $ssn = $this->getRequest()->getParam('ssn');
        $type = ($this->getRequest()->getParam('type') == 1) ? true : false;
        $countryCode = $this->getRequest()->getParam('cc');

        // Credentials
        $conf = Mage::getStoreConfig('payment/svea_invoice');
        $conf['company'] = $type;

        try {
            $result = Mage::helper('svea_webpay')->getAddresses($ssn, $countryCode, $conf);
        } catch (Exception $e) {
            $result = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}