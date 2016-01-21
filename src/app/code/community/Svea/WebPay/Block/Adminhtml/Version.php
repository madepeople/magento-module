<?php

/**
 * Admihhtml field block that displays the current Svea_WebPay version
 */
class Svea_Webpay_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return (string)Mage::getConfig()->getNode()->modules->Svea_WebPay->version;
    }

}