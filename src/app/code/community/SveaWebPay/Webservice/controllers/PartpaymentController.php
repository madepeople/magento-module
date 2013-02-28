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
 
class SveaWebPay_Webservice_PartpaymentController extends Mage_Core_Controller_Front_Action
{
    private function getCampains($priceIncludingVAT = null)
    {
        $campains = Mage::getModel("sveawebpayws/campains");
        return $campains->getPaymentplanCampains($priceIncludingVAT);
    }
    
    private function getInnerRequestValue($key)
    {
        $request  = Mage::app()->getRequest();
        return ($request->isPost()) ? $request->getPost($key) : $request->getQuery($key);
    }
    
    public function GetCampainsJSONAction()
    {
        // The price REQUEST:key should be with VAT.
        $price = $this->getInnerRequestValue("price");
        $result = $this->getPaymentplanCampains($price);
        
        $foundAnyCampains = (!empty($result)) ? true : false;
        $statusWithinInterval = ((!$foundAnyCampains) && $price !== null) ? " within the interval of your price: " . $price : ""; 
        $status = (!$foundAnyCampains) ? "Could not find any campains" . $statusWithinInterval . "." : "";
        
        $json = Mage::helper("sveawebpayws/json");
        echo $json->encode($result,$foundAnyCampains,$status);
    }
        
    public function GetCampainsViewJSONAction()
    {    
        // The price REQUEST:key should be with VAT.
        $price = $this->getInnerRequestValue("price");
        $result = $this->geCampains($price);
        
        $foundAnyCampains = (!empty($result)) ? true : false;
        $statusWithinInterval = ((!$foundAnyCampains) && $price !== null) ? " within the interval of your price: " . $price : ""; 
        $status = (!$foundAnyCampains) ? "Could not find any campains" . $statusWithinInterval . "." : "";
        
        // Get view and get information by..... 
        $result = "" . var_export($result,true);
        $json = Mage::helper("sveawebpayws/json");
        echo $json->encode($result,$foundAnyCampains,$status);
    }
    
    public function RenderCampainsItemPriceAction()
    {
        // The price REQUEST:key should be with VAT.
        $price = $this->getInnerRequestValue("price");
        $result = $this->getCampains($price);
        
        $foundAnyCampains = (!empty($result)) ? true : false;
        $statusWithinInterval = ((!$foundAnyCampains) && $price !== null) ? " within the interval of your price: " . $price : ""; 
        $status = (!$foundAnyCampains) ? "Could not find any campains" . $statusWithinInterval . "." : "";
        
        // Get view and get information by..... 
        $result = "" . var_export($result,true);
        
        // "Render"
        echo $result;
    }
}