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
class SveaWebPay_Hosted_Block_Sales_Order_Totals_Item extends Mage_Adminhtml_Block_Sales_Order_Totals_Item
{
    private $price = 0;
    private $basePrice = 0;
    private $showHandlingfee = false;
    
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        $source = $this->getSource();
        if(!is_null($source))
        {               
            $title = "";
            $order = null;
            
            // Get the order instance.
            if($source instanceof Mage_Sales_Model_Order)
                $order = $source;
                
            else if($source instanceof Mage_Sales_Model_Order_Invoice)
                $order = $source->getOrder();
            
            // Checking if the order is equal to null, hence we can't continue if so.
            if(!is_null($order))
            {
                // Get the method instance.
                $payment = $order->getPayment();
                if(!is_null($payment))
                {
                    // now that we have the method we shall get the configurations and show the handlingfee.
                    $method = $payment->getMethodInstance();
                    if(!is_null($method))
                    {
                        $incrementId = $order->getIncrementId();
                        
                        $calculations = Mage::helper("swpcommon/calculations");
                        $handlingfees = $calculations->getHandlingfeeTotal($order);
                        $title .= var_export($handlingfees,true);
                        
                        if(is_array($handlingfees) &&
                            key_exists("base_value",$handlingfees) && key_exists("value",$handlingfees) &&
                            key_exists("base_tax",$handlingfees) && key_exists("tax",$handlingfees))
                        {
                            
                            $description = $method->getConfigData('handling_fee_description');
                            $displayType = $method->getConfigData('handling_fee_display_order');
                            
                            // Including tax.
                            if($displayType == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX)
                            {
                                $this->price = $handlingfees["value"] + $handlingfees["tax"];
                                $this->basePrice = $handlingfees["base_value"] + $handlingfees["base_tax"];
                            }
                            
                            // Excluding tax.
                            else
                            {
                                $this->price = $handlingfees["value"];
                                $this->basePrice = $handlingfees["base_value"];
                            }
                            
                            // Show the handlingfee.
                            $this->showHandlingfee = ($this->price > 0 || $this->basePrice > 0) ? true : false;
                            
                            // Set label.
                            $this->setLabel($description);
                        }
                    }    
                }
            }
            
            // this is to prevent getting a null value for the label.
            else
                $this->setLabel("SveaWebPay - Handlingfee: " . $title);
        }
        
        $this->setCanDisplayTotalPaid($this->getParentBlock()->getCanDisplayTotalPaid());
        $this->setCanDisplayTotalRefunded($this->getParentBlock()->getCanDisplayTotalRefunded());
        $this->setCanDisplayTotalDue($this->getParentBlock()->getCanDisplayTotalDue());
    }
    
    public function showHandlingfee()
    {
        return $this->showHandlingfee;
    }
    
    public function displayPrices($basePrice, $price, $strong = false, $separator = '<br/>')
    {   
        if ($this->getOrder()->isCurrencyDifferent()) {
            $res = '<strong>';
            $res.= $this->getOrder()->formatBasePrice($this->basePrice);
            $res.= '</strong>'.$separator;
            $res.= '['.$this->getOrder()->formatPrice($this->price).']';
        }
        else {
            $res = $this->getOrder()->formatPrice($this->price);
            if ($strong) {
                $res = '<strong>'.$res.'</strong>';
            }
        }
        return $res;
    }
}