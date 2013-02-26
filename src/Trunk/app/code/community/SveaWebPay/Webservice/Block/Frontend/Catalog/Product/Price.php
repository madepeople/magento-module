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
 
class SveaWebPay_Webservice_Block_Frontend_Catalog_Product_Price extends Mage_Bundle_Block_Catalog_Product_Price
{
    private function isPartpaymentActivated()
    {
        return (Mage::getStoreConfig("payment/swpwspartpay/active",Mage::app()->getStore()->getId())) ? true : false;
    }
    
    private function CreateBlock()
    {
        $log = Mage::helper("swpcommon/log");
        $layout = $this->getLayout();
        if(!$layout)
        {
            $log->log("Failed to retrieve layout from inside of catalog product price block.");
            return null;
        }
        
        $block = $layout->createBlock("core/template","swpwspartpay_productcampaininfo_block");
        if(!$block)
        {
            $log->log("Failed to create block from inside of catalog product price block.");
            return null;
        }
        
        return $block;
    }
    
    private function RenderInfoBlockToString($price,$pricePerMonth)
    {
        $block = $this->CreateBlock();
        if(!$block)
            return "";
        
        $block->setTemplate("sveawebpay/webservice/catalog/product/price.phtml");
        $block->setData("useExtendedInfo",false);   
        $block->setData("pricePerMonth",$pricePerMonth);
        $block->setData("price",$price);
        return $block->toHtml();                                    
    }
      
    private function RenderExtendedInfoBlockToString($price,$pricePerMonth,$campains = null) {
        $block = $this->CreateBlock();
        if(!$block)
            return "";
        
        $block->setTemplate("sveawebpay/webservice/catalog/product/price.phtml");
        $block->setData("useExtendedInfo",true);
        $block->setData("campains",$campains);
        $block->setData("pricePerMonth",$pricePerMonth);
        $block->setData("price",$price);
        return $block->toHtml();                      
    }
          
    protected function _toHtml()
    {
        $parentHtml = parent::_toHtml();
        $extendedHtml = "";
        
        $price = 0;
        $currencyRate = 1;
        
        $calculationHelper = Mage::helper("swpcommon/calculations");
        $currencyRate = $calculationHelper->getBaseToCurrentCurrencyRate();
        if($currencyRate == null)
            return $parentHtml;
            
        $taxHelper = $this->helper('tax');
        if ($this->getProduct()->getSpecialPrice() > 0)
            $price = ($taxHelper->getPrice($this->getProduct(), $this->getProduct()->getSpecialPrice(), true)) * $currencyRate;
        else
            $price = ($taxHelper->getPrice($this->getProduct(), $this->getProduct()->getFinalPrice(), true)) * $currencyRate;
    

        // We should not continue if we doesnt event have the partpayment extension activated.
        if(!$this->isPartpaymentActivated())
            return $parentHtml;

        $isCategoryPage = strstr(strtolower(Mage::app()->getFrontController()->getRequest()->getRequestUri()), "/category/view/");
        $isCatalog = (Mage::app()->getFrontController()->getRequest()->getRouteName() == "catalog");
        
        $campainsModel = Mage::getModel("sveawebpayws/campains");
        $firstCampain = $campainsModel->getPayementplanCampain( $price );        
        $suffix = $this->getIdSuffix();
        if ($this->getTemplate() == "catalog/product/price.phtml" || $this->getTemplate() == "bundle/catalog/product/price.phtml")
        {
            if($suffix == null && $firstCampain !== null && !$isCategoryPage)
            {
                $monthlyannuity = $firstCampain["monthlyannuity"];
                $campains = $campainsModel->getPaymentplanCampains($price);
                $extendedHtml .= $this->RenderExtendedInfoBlockToString($price, $monthlyannuity, $campains);
            }
            
            else if($suffix != "_clone" && $suffix != "-related" && $firstCampain !== null)
            {
                $monthlyannuity = $firstCampain["monthlyannuity"];
                $extendedHtml .= $this->RenderInfoBlockToString( $price, $monthlyannuity );
            }
        }
        return $parentHtml . $extendedHtml;
    }
}
