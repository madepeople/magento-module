<?php
/**
 * This block prints an update button in the payment plan administation interface
 * that's used for updating payment plan/campaign information
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Block_PaymentPlanParams_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_template = 'svea/system/config/params-button.phtml';

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return AJAX URL for the payment plan params update button. We also
     * pass the current configuration scope + id to the controller action.
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        $form = $this->getForm();
        switch ($form->getScope()) {
            case 'stores':
                $append = '/scope/store/id/' . $form->getScopeId();
                break;
            case 'websites':
                $append = '/scope/website/id/' . $form->getScopeId();
                break;
            default:
                $append = '';
                break;
        }
        
        return Mage::helper('adminhtml')->getUrl('adminhtml/paymentplanparams/check' . $append,
                array('_secure' => true));
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setId('paymentPlanParams_button')
                ->setLabel($this->helper('adminhtml')->__('Update'))
                ->setOnclick('sveaUpdatePaymentPlanParams(); return false;');
        
        $latestUpdateTime = Mage::helper('svea_webpay')
                ->getLatestUpdateOfPaymentPlanParams();

        return $button->toHtml() . '<div id="sveaParamsLastUpdated">Last update: '
                . date('Y-m-d H:i:s', (int)$latestUpdateTime) . '</div>';
    }
}
