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
 
require_once(Mage::getBaseDir("lib") ."/SveaWebPay/Webservice/Service.php");

class SveaWebPay_Webservice_Model_Campains extends Mage_Core_Model_Abstract
{    
    private function saveWebserviceResultArrayIntoDatabase($array = Array())
    {
        $log = Mage::helper("swpcommon/log");
        $storeId = Mage::app()->getStore()->getRootCategoryId();
        if($array != null && !empty($array) && $storeId !== null)
        {
            foreach($array as $campainData)
            {
                try
                {
                    $campain = Mage::getModel("sveawebpayws/campains");
                    if(key_exists("campaincode",$campainData)) $campain->setCampaincode($campainData["campaincode"]);
                    if(key_exists("description",$campainData)) $campain->setDescription($campainData["description"]);
                    if(key_exists("paymentplantype",$campainData)) $campain->setPaymentplantype($campainData["paymentplantype"]);
                    if(key_exists("contractlength",$campainData)) $campain->setContractlength($campainData["contractlength"]);
                    if(key_exists("monthlyannuityfactor",$campainData)) $campain->setMonthlyannuityfactor($campainData["monthlyannuityfactor"]);
                    if(key_exists("initialfee",$campainData)) $campain->setInitialfee($campainData["initialfee"]);
                    if(key_exists("notificationfee",$campainData)) $campain->setNotificationfee($campainData["notificationfee"]);
                    if(key_exists("interestratepercentage",$campain)) $campain->setInterestratepercentage($campainData["interestratepercentage"]);
                    if(key_exists("interestfreemonths",$campainData)) $campain->setInterestFreeMonths($campainData["interestfreemonths"]);
                    if(key_exists("paymentfreemonths",$campainData)) $campain->setPaymentFreeMonths($campainData["paymentfreemonths"]);
                    if(key_exists("fromamount",$campainData)) $campain->setFromamount($campainData["fromamount"]);
                    if(key_exists("toamount",$campainData)) $campain->setToamount($campainData["toamount"]);
                    
                    // This is vital hence the campains will be overwritten by different stores otherwise.
                    $campain->setStoreid($storeId);                         
                    $campain->setTimestamp(time());
                    $campain->save();
                }
                catch(Exception $exception)
                {
                    $log->log("Exception caught while saving campains to database. Exception given: " . $exception->getMessage());
                    return false;
                }
            }
        }
        else
        {
            $log->log("Could not save webservice information to database. Reason: No paymentplanparams was found.");
            return false;
        }
        return true;
    }
    
    private function updateCampaincacheIfNeeded()
    {
        $cacheinterval = 24; // Wait for up to 24 hours until cmpain update.
        $oldCampains = Array();
        $timestamp = time();
        $currentDate = getdate($timestamp);
        $storeId = Mage::app()->getStore()->getRootCategoryId();
        
        // Make sure that we should update our cache before making a call to our webservice.
        $needToUpdate = false;
        $session = Mage::getModel("sveawebpayws/session");
        $campains = Mage::getModel("sveawebpayws/campains");
        $collection = $campains->getCollection();
        $collection->addFilter("storeid",$storeId);
        
        // Need to do this check so that we make a request when we've first installed the module
        if($collection->getSize() <= 0)
        {
            if($session->getCampainsUpdateRequested() !== true)
            {
                $needToUpdate = true;
                $session->setCampainsUpdateRequested( true );
            }
        }

        // If we have campains we should execute them.
        else
        {
            foreach($collection as $campain)
            {
                $campainTimestamp = $campain->getTimestamp();
                $campainDate = getdate($campainTimestamp);
                if ($campainTimestamp < $timestamp && $campainDate["hours"] <= ($currentDate["hours"] - $cacheinterval))
                {
                    $oldCampains[ $campain->getId() ] = $campain;
                    $needToUpdate = true;
                    $session->unsCampainsUpdateRequested();
                }
            }
        }

        // Update cache.
        $cleanupInTheDatabase = false;
        if($needToUpdate)
        {
            // Get information from our webservice calls.
            $information = $this->getWebserviceInformation();
            if($information !== null)
            {
                // Save the information we've got to the database
                if($this->saveWebserviceResultArrayIntoDatabase($information) !== false)
                {
                    $cleanupInTheDatabase = true;
                }
            }
        }
        
        // Rember to cleanup.
        if(!empty($oldCampains) && $cleanupInTheDatabase)
        {
            foreach($oldCampains as $campain)
            {
                $campain->delete();
            }
        }
        return true;
    }
    
    
    private function getWebserviceInformation()
    {
        $method = Mage::getModel("sveawebpayws/paymentmethod_partpay");
        $username = null;
        $password = null;
        $accnr =  null;
        $url = null;
        
        if($method !== null)
        {
            $username = $method->getConfigData("username");
            $password = $method->getConfigData("password");
            $accnr = $method->getConfigData("accnr");
    
            $testmode = $method->getConfigData( "test" );
            $helperUrl = Mage::helper("swpcommon/url");
            $url = $helperUrl->getWebservice($testmode);
        }
        
        if($username == null || $password == null  || $accnr == null || $url == null)
            return null;
        
        
        // Save information based from dataase.
        $service = new Service( $url );
        $clientAuthInfo = new ClientAuthInfo();
        $clientAuthInfo->ClientNumber = $accnr;
        $clientAuthInfo->Username = $username;
        $clientAuthInfo->Password = $password;
        
        $paymentPlanParamsRequest = new GetPaymentPlanParamsRequest();
        $paymentPlanParamsRequest->Auth = $clientAuthInfo;
        
        $request = new GetPaymentPlanParams();
        $request->request = $paymentPlanParamsRequest;
        
        $response = $service->GetPaymentPlanParams( $request );
        $result = ($response != null && isset($response->GetPaymentPlanParamsResult)) ? $response->GetPaymentPlanParamsResult : null;
        
        if($result != null && isset($result->Accepted) && $result->Accepted == true)
        {
            return $this->getWebserviceResultDataAsArray($result);
        }
        else
        {
            $log = Mage::helper("swpcommon/log");
            $rejectionCode = (isset($response->RejectionCode)) ? $response->RejectionCode : "";
            $errorMessage = (isset($response->ErrorMessage)) ? $response->ErrorMessage : "";
            $log->log("Could not fetch webservice information paymentplanparams. ErrorMessage given: " . $errorMessage . " RejectionCode: " . $rejectionCode );
        }
        return null;
    }
    
    /*
        Array keys:
            campaincode (string)
            description (string)
            paymentplantype (string)
            contractlength (int)
            monthlyannuityfactor (double)
            initialfee (double)
            notificationfee (double)
            interestratepercentage (double)
            interestfreemonths (int)
            paymentfreemonths (int)
            fromamount (double)
            toamount (double)
    */
    
    private function getWebserviceResultDataAsArray($responseResult = null)
    {
        $result = Array();
        if( $responseResult == null)
            return $result;
        
        $log = Mage::helper("swpcommon/log");
        if($responseResult != null && isset($responseResult->CampainCodes) && isset($responseResult->CampainCodes->CampainCodeInfo))
        {
            foreach( $responseResult->CampainCodes->CampainCodeInfo as $responseResultItem)
            {
                try
                {
                    $campainCode = (isset($responseResultItem->CampainCode)) ? $responseResultItem->CampainCode : "";
                    $description = (isset($responseResultItem->Description)) ? $responseResultItem->Description : "";
                    $paymentplantype = (isset($responseResultItem->PaymentPlanType)) ? $responseResultItem->PaymentPlanType : "";
                    $contractlength = (isset($responseResultItem->ContractLengthInMonths)) ? $responseResultItem->ContractLengthInMonths : "";
                    $monthlyannuityfactor = (isset($responseResultItem->MonthlyAnnuityFactor)) ? $responseResultItem->MonthlyAnnuityFactor : "";
                    $initialfee = (isset($responseResultItem->InitialFee)) ? $responseResultItem->InitialFee : "";
                    $notificationfee = (isset($responseResultItem->NotificationFee)) ? $responseResultItem->NotificationFee : "";
                    $interestratepercentage = (isset($responseResultItem->InterestRatePercent)) ? $responseResultItem->InterestRatePercent : "";
                    $interestfreemonths = (isset($responseResultItem->NrOfInterestFreeMonths)) ? $responseResultItem->NrOfInterestFreeMonths : "";
                    $paymentfreemonths = (isset($responseResultItem->NrOfPaymentFreeMonths)) ? $responseResultItem->NrOfPaymentFreeMonths : "";
                    $fromamount = (isset($responseResultItem->FromAmount)) ? $responseResultItem->FromAmount : "";
                    $toamount = (isset($responseResultItem->ToAmount)) ? $responseResultItem->ToAmount : "";
                    
                    $result[] = Array(
                            "campaincode" => $campainCode,
                            "description" => $description,
                            "paymentplantype" => $paymentplantype,
                            "contractlength" => $contractlength,
                            "monthlyannuityfactor" => $monthlyannuityfactor,
                            "initialfee" => $initialfee,
                            "notificationfee" => $notificationfee,
                            "interestratepercentage" => $interestratepercentage,
                            "interestfreemonths" => $interestfreemonths,
                            "paymentfreemonths" => $paymentfreemonths,
                            "fromamount" => $fromamount,
                            "toamount" => $toamount
                        );
                }
                catch(Exception $exception)
                {
                    $log->log("Exception caught inside of campains.php while parsing paymentplanparams information. Exception given: " . $exception->getMessage());
                }
            }
        }
        else
        {
            $log->log("Could not update database with new campains. No campains was found.");
        }
        return $result;
    }

 
    private function getCampainsFromDatabase()
    {
        
        $result = Array();
        $storeId = Mage::app()->getStore()->getRootCategoryId();
        $campains = Mage::getModel("sveawebpayws/campains");
        $campainsCollection = $campains->getCollection();
        
        // This is to prevent data from other countrys (stores).
        $campainsCollection->addFilter("storeid", $storeId);

        foreach($campainsCollection as $campain)
        {
            $result[] = Array(
                    "campaincode" => $campain->getCampaincode(),
                    "description" => $campain->getDescription(),
                    "paymentplantype" => $campain->getPaymentplantype(),
                    "contractlength" => $campain->getContractlength(),
                    "monthlyannuityfactor" => $campain->getMonthlyannuityfactor(),
                    "initialfee" => $campain->getInitialfee(),
                    "notificationfee" => $campain->getNotificationfee(),
                    "interestratepercentage" => $campain->getInterestratepercentage(),
                    "interestfreemonths" => $campain->getInterestFreeMonths(),
                    "paymentfreemonths" => $campain->getPaymentFreeMonths(),
                    "fromamount" => $campain->getFromamount(),
                    "toamount" => $campain->getToamount()
                );
        }
        return $result;
    }
 
    private static function sortCampainCodes($a, $b)
    {
        // Longest campain code first.
        if ($a["contractlength"] == $b["contractlength"])
            return 0;
            
        return ($a["contractlength"] > $b["contractlength"]) ? -1 : 1;
    }

    public function getPaymentplanCampains($priceIncludingVAT = null)
    {
        // If we need to cache our data do so.
        $this->updateCampaincacheIfNeeded();

        // Get all the campains stored in the database
        $campains = $this->getCampainsFromDatabase();

        // Get all campains that is within the interval of the campain, only if the parameter to this method is set.
        if($priceIncludingVAT !== null)
        {
            $result = Array();
            
            // Sort the array.
            if(!empty($campains))
            {
                uasort($campains,"SveaWebPay_Webservice_Model_Campains::sortCampainCodes");
            }
            foreach($campains as $campain)
            {
                // Check so that we are within the interval of this campain.
                if($campain["fromamount"] <= $priceIncludingVAT && $priceIncludingVAT <= $campain["toamount"])
                {
                    $campain["monthlyannuity"] = $this->getPricePerMonth($priceIncludingVAT,$campain);
                    $result[] = $campain;
                }
            }
            return $result;
        }

        // Otherwise just return the all campains.
        return $campains;
    }

    public function getPayementplanCampain($price = null)
    {
        if($price === null)
            return null;
    
        $resultCampain = null;
        $campains = $this->getPaymentplanCampains($price);
        
        if($campains !== null && !empty($campains))
            $resultCampain = $campains[0];
            
        return $resultCampain;
    }

    protected function getPricePerMonth($price, $campainArray = Array())
    {
        if(!is_array($campainArray) || empty($campainArray))
            return null;
            
        return (($price * (double)$campainArray["monthlyannuityfactor"]) + $campainArray["notificationfee"]);
    }
    
    protected function _construct()
    {
        $this->_init("sveawebpayws/campains");
    }
}