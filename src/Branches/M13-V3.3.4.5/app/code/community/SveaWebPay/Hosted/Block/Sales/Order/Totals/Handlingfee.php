<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml order totals block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class SveaWebpay_Hosted_Block_Sales_Order_Totals_Handlingfee extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    /**
     * Retrieve required options from parent
     */
    protected function _beforeToHtml()
    {
        if (!$this->getParentBlock()) {
            Mage::throwException(Mage::helper('adminhtml')->__('Invalid parrent block for this block'));
        }
        $this->setOrder($this->getParentBlock()->getSource());

        parent::_beforeToHtml();
    }

    private function isActivated() 
    {
        $helper = Mage::helper("swpcommon");
        $model = Mage::getModel('sveawebpay/source_methods');
        
        $order = $this->getOrder();
        if(!$order)
            return false;
        
        $methods = $model->getPaymentMethods();
        if(!$helper->isMethodActive($methods,$order))
            return false;
        
        if(!$helper->isHandlingfeeEnabled($methods,$order))
            return false;    
        
        $payment = $order->getPayment();
        if(!$payment || !$payment->hasMethodInstance())
            return false;
        
        $paymentMethod = $payment->getMethodInstance();
        if(!$paymentMethod)
            return false;
            
        return true;
    } 

    public function getFullTaxInfo()
    {
        $rates = Mage::getModel('sales/order_tax')->getCollection()->loadByOrder($this->getOrder())->toArray();
        return Mage::getSingleton('tax/calculation')->reproduceProcess($rates['items']);
    }

    public function displayAmount($amount, $baseAmount)
    {
        
        if(!$this->isActivated())
            return $this->displayPrices($baseAmount, $amount, false, '<br />');

        $calculations = Mage::helper("swpcommon/calculations");
        
        // Get information from order object.
        $order = $this->getOrder();
        
        // Build up handlingfee information.
        $handlingfees = $calculations->getHandlingfeeTotal($order);
        
        // Get and pass the handlingfee values to be renderered.
        $amount = $handlingfees["value"] + $handlingfees["tax"];
        $baseAmount = $handlingfees["base_value"] + $handlingfees["base_tax"];

        // Magento's method call.
        return $this->displayPrices($baseAmount, $amount, false, '<br />');
    }
}
