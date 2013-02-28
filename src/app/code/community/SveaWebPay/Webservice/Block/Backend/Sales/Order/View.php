<?php
 
/**
 * SveaWebPay Payment Module for Magento.
 *   Copyright (C) 2012  SveaWebPay
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Any questions may be directed to kundtjanst.sveawebpay@sveaekonomi.se
 */
 
class SveaWebPay_Webservice_Block_Backend_Sales_Order_View extends Mage_Core_Block_Template
{ 
    public function initTotals()
    {
        $helper = Mage::helper("swpcommon");
        $model  = Mage::getModel('sveawebpayws/source_methods');
        
        $totals = Array();
        
        $order = $this->getParentBlock()->getOrder();
        if(!$order)
            return $this;
        
        $methods = $model->getPaymentMethods();
        if(!$helper->isMethodActive($methods,$order))
            return $this;
        
        if(!$helper->isHandlingfeeEnabled($methods,$order))
            return $this;    
        
        $payment = $order->getPayment();
        if(!$payment || !$payment->hasMethodInstance())
            return $this;
        
        $paymentMethod = $payment->getMethodInstance();
        if(!$paymentMethod)
            return $this;
        
        $calculations = Mage::helper("swpcommon/calculations");
        $handlingfees = $calculations->getHandlingfeeTotal($order);
        
        $handlingfeeLabel = $paymentMethod->getConfigData("handling_fee_description");
        $displayType      = $paymentMethod->getConfigData('backend_handling_fee_display_order');
        

        if($displayType == Mage_Tax_Model_Config::DISPLAY_TYPE_BOTH)
        {
            $total = new Varien_Object(array(
                    'code'      => 'handlingfee_excl',
                    'strong'    => false,
                    'value'     => $handlingfees["value"],
                    'base_value'=> $handlingfees["base_value"],
                    'label'     => $handlingfeeLabel." (Excl.Tax)",
                    'area'      => '',
                    'before'    => 'grand_total' 
                ));
            $totals[] = $total;
            
            $total = new Varien_Object(array(
                    'code'      => 'handlingfee',
                    'strong'    => false,
                    'value'     => $handlingfees["value"] + $handlingfees["tax"],
                    'base_value'=> $handlingfees["base_value"] + $handlingfees["base_tax"],
                    'label'     => $handlingfeeLabel." (Incl.Tax)",
                    'area'      => '',
                    'before'    => 'handlingfee_excl'
                ));
            $totals[] = $total;
        }
        else if($displayType == Mage_Tax_Model_Config::DISPLAY_TYPE_INCLUDING_TAX)
        {
            $total = new Varien_Object(array(
                    'code'      => 'handlingfee',
                    'strong'    => false,
                    'value'     => $handlingfees["value"] + $handlingfees["tax"] ,
                    'base_value'=> $handlingfees["base_value"] + $handlingfees["base_tax"],
                    'label'     => $handlingfeeLabel,
                    'area'      => '',
                    'before'    => 'grand_total'
                ));
            $totals[] = $total;
        }
        
        // Excluding
        else
        {
            $total = new Varien_Object(array(
                    'code'      => 'handlingfee',
                    'strong'    => false,
                    'value'     => $handlingfees["value"],
                    'base_value'=> $handlingfees["base_value"],
                    'label'     => $handlingfeeLabel,
                    'area'      => '',
                    'before'    => 'grand_total'
                ));
            $totals[] = $total;
        }
        
        
        foreach ($totals as $total)
            $this->getParentblock()->addTotalBefore($total,explode(',',$total['before']));
        
        return $this;
    }
}
?>