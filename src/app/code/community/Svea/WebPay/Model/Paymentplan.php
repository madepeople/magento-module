<?php

require_once Mage::getRoot() . '/code/community/Svea/WebPay/integrationLib/Includes.php';

/**
 * Class for Updating PaymentPlan params to table
 *
 * @category Payment
 * @package Svea_WebPay_Module_Magento
 * @author SveaWebPay <https://github.com/sveawebpay/magento-module>
 * @license https://github.com/sveawebpay/magento-module/blob/master/LICENSE.txt Apache License
 * @copyright (c) 2013, SveaWebPay (Svea Ekonomi AB)
 *
 */
class Svea_WebPay_Model_Paymentplan extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('svea_webpay/paymentplan');
    }

    /**
     * Get params from Svea API
     *
     * @throws Mage_Core_Exception If 'client_country' paymentplan config option isn't set
     *
     * @return type response or error
     */
    public function getPaymentPlanParams($id = null, $scope = null)
    {
        switch ($scope) {
            case 'store':
                $paymentMethodConfig = Mage::getStoreConfig('payment/svea_paymentplan', $id);
                break;
            case 'website':
                $paymentMethodConfig = Mage::app()->getWebsite($id)
                        ->getConfig('payment/svea_paymentplan');
                break;
            default:
                $paymentMethodConfig = Mage::getStoreConfig('payment/svea_paymentplan');
                break;
        }

        if (!array_key_exists('client_country', $paymentMethodConfig)) {
            throw new Mage_Core_Exception("Error: Client Country not set.");
        }

        $conf = new SveaMageConfigProvider($paymentMethodConfig);
        $sveaObject = WebPay::getPaymentPlanParams($conf);
        $response = $sveaObject->setCountryCode($paymentMethodConfig['client_country'])
                               ->doRequest();

        if ($response->accepted == 1) {
            return $this->formatResponse($response);
        } else {
            return $response->resultcode . " : " . $response->errormessage;
        }
    }

    /**
     * Insert data into table
     */
    public function updateParamTable($id = null, $scope = null)
    {
        switch ($scope) {
            case 'store';
                $storeId = $id;
                break;
            default:
                $storeId = 0;
        }

        $paramArray = $this->getPaymentPlanParams($id, $scope);
        if (!is_array($paramArray)) {
            Mage::throwException($paramArray);
        }

        foreach ($paramArray as $campaignData) {
            try {
                Mage::getModel("svea_webpay/paymentplan")
                        ->setData($campaignData)
                        ->setTimestamp(time())
                        ->setStoreid($storeId)
                        ->save();
            } catch (Exception $exception) {
                Mage::throwException($exception->getMessage());
            }
        }
    }

    /**
     * Get an object which is similar to a Svea Response
     *
     * This can be used instead of a proper svea response since we don't store
     * them in the database. Do not assume that the result will contain _all_
     * information that a real Svea Response has, test your code when using this
     * result.
     *
     * @returns stdClass An object with attributes set as a PaymentPlanResponse
     */
    public function asSveaResponse()
    {
        $rc = new stdClass();

        $map = array(
            "campaincode" => 'campaignCode',
            "description" => 'description',
            "paymentplantype" => 'paymentPlanType',
            "contractlength" => 'contractLength',
            "monthlyannuityfactor" => 'monthlyAnnuityFactor',
            "initialfee" => 'initialFee',
            "notificationfee" => 'notificationFee',
            "interestratepercentage" => 'interestRatePercent',
            "interestfreemonths" => 'numberOfInterestFreeMonths',
            "paymentfreemonths" => 'numberOfPaymentFreeMonths',
            "fromamount" => 'fromAmount',
            "toamount" => 'toAmount',
        );

        foreach ($map as $myKey => $responseKey) {
            $rc->$responseKey = $this->getData($myKey);
        }

        return $rc;
    }

    /**
     * Format Svea response to Array
     *
     * @param type $response
     * @return array
     */
    public function formatResponse($response)
    {
        $result = array();
        if ($response == null) {
            return $result;
        } else {
            foreach ($response->campaignCodes as $responseResultItem) {
                try {
                    $campainCode = (isset($responseResultItem->campaignCode)) ? $responseResultItem->campaignCode : "";
                    $description = (isset($responseResultItem->description)) ? $responseResultItem->description : "";
                    $paymentplantype = (isset($responseResultItem->paymentPlanType)) ? $responseResultItem->paymentPlanType : "";
                    $contractlength = (isset($responseResultItem->contractLengthInMonths)) ? $responseResultItem->contractLengthInMonths : "";
                    $monthlyannuityfactor = (isset($responseResultItem->monthlyAnnuityFactor)) ? $responseResultItem->monthlyAnnuityFactor : "";
                    $initialfee = (isset($responseResultItem->initialFee)) ? $responseResultItem->initialFee : "";
                    $notificationfee = (isset($responseResultItem->notificationFee)) ? $responseResultItem->notificationFee : "";
                    $interestratepercentage = (isset($responseResultItem->interestRatePercent)) ? $responseResultItem->interestRatePercent : "";
                    $interestfreemonths = (isset($responseResultItem->numberOfInterestFreeMonths)) ? $responseResultItem->numberOfInterestFreeMonths : "";
                    $paymentfreemonths = (isset($responseResultItem->numberOfPaymentFreeMonths)) ? $responseResultItem->numberOfPaymentFreeMonths : "";
                    $fromamount = (isset($responseResultItem->fromAmount)) ? $responseResultItem->fromAmount : "";
                    $toamount = (isset($responseResultItem->toAmount)) ? $responseResultItem->toAmount : "";

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
                } catch (Exception $e) {
                    Mage::throwException($this->_getHelper()->__($e->getMessage()));
                }
            }
        }
        return $result;
    }

}
