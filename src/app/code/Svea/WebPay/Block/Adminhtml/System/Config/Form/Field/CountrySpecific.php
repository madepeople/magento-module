<?php

/**
 * The purpose of this file is to render the country-selector javascript box
 * responsible for allowing the store owner to set multiple client numbers
 * depending on the customer invoice address
 *
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Block_Adminhtml_System_Config_Form_Field_CountrySpecific
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '<h1>HELLO</h1>';
//        $element->setValue(Mage::app()->loadCache('admin_notifications_lastcheck'));
//        $format = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
//        return Mage::app()->getLocale()->date(intval($element->getValue()))->toString($format);
    }
}