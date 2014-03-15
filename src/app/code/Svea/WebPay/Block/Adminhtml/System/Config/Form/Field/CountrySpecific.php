<?php

/**
 * The purpose of this file is to render the country-selector javascript box
 * responsible for allowing the store owner to set multiple client numbers
 * depending on the customer invoice address
 *
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Block_Adminhtml_System_Config_Form_Field_CountrySpecific
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
//    public function render(Varien_Data_Form_Element_Abstract $element)
//    {
//        // Replace [value] with [inherit]
//        $namePrefix = preg_replace('#\[value\](\[\])?$#', '', $element->getName());
//
//        $options = $element->getValues();
//
//        $addInheritCheckbox = false;
//        if ($element->getCanUseWebsiteValue()) {
//            $addInheritCheckbox = true;
//            $checkboxLabel = Mage::helper('adminhtml')->__('Use Website');
//        }
//        elseif ($element->getCanUseDefaultValue()) {
//            $addInheritCheckbox = true;
//            $checkboxLabel = Mage::helper('adminhtml')->__('Use Default');
//        }
//
//        if ($addInheritCheckbox) {
//            $inherit = $element->getInherit()==1 ? 'checked="checked"' : '';
//            if ($inherit) {
//                $element->setDisabled(true);
//            }
//        }
//
//        $html = '<td colspan="2">';
//        $html .= '
//<div class="section-config" style="padding-right: 25px;">
//    <div class="entry-edit-head collapseable">Country Specific Settings</div>
//    <fieldset class="config collapseable">
//        <div class="section-config">
//            <select></select>
//        </div>
//    </fieldset>
//</div>
//';
//        $html .= '</td>';
//        return $html;
//    }
//    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
//    {
//        return '
//<div class="section-config">
//    <div class="entry-edit-head collapseable">Country Specific Settings</div>
//    <fieldset class="config collapseable">
//        <div class="section-config">
//            <select></select>
//        </div>
//    </fieldset>
//</div>
//';
//    }
}