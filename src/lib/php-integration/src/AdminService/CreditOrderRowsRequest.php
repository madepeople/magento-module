<?php
namespace Svea\AdminService;

require_once SVEA_REQUEST_DIR . '/Includes.php';
require_once 'AdminServiceRequest.php';

/**
 * Admin Service CreditOrderRowsRequest class
 * 
 * @author Kristian Grossman-Madsen
 */
class CreditOrderRowsRequest extends AdminServiceRequest {
    
    /** @var CreditOrderRowBuilder $orderBuilder */
    public $orderBuilder;
    
    /** @var SoapVar[] $rowNumbers  initally empty, contains the indexes of all order rows that will be credited */
    public $rowNumbers; 
    
    /** @var SoapVar[] $orderRows  initially empty, specifies any additional credit order rows to credit */    
    public $orderRows;

    /**
     * @param creditOrderRowsBuilder $orderBuilder
     */
    public function __construct($creditOrderRowsBuilder) {
        $this->action = "CreditInvoiceRows";
        $this->orderBuilder = $creditOrderRowsBuilder;
        $this->rowNumbers = array();
        $this->orderRows = array();
    }

    /**
     * populate and return soap request contents using AdminSoap helper classes to get the correct data format
     * @return Svea\AdminSoap\CreditOrderRowsRequest
     * @throws Svea\ValidationException
     */
    public function prepareRequest() {        
                   
        $this->validateRequest();
        
        foreach( $this->orderBuilder->creditOrderRows as $orderRow ) {      

            // handle different ways to spec an orderrow            
            // inc + ex
            if( !isset($orderRow->vatPercent) && (isset($orderRow->amountExVat) && isset($orderRow->amountIncVat)) ) {
                $orderRow->vatPercent = WebServiceRowFormatter::calculateVatPercentFromPriceExVatAndPriceIncVat($orderRow->amountIncVat, $orderRow->amountExVat );
            }
            // % + inc
            elseif( (isset($orderRow->vatPercent) && isset($orderRow->amountIncVat)) && !isset($orderRow->amountExVat) ) {
                $orderRow->amountExVat = WebServiceRowFormatter::convertIncVatToExVat($orderRow->amountIncVat, $orderRow->vatPercent);
            }
            // % + ex, no need to do anything

            $this->orderRows[] = new \SoapVar( 
                new AdminSoap\OrderRow(
                    $orderRow->articleNumber, 
                    $orderRow->name.": ".$orderRow->description,
                    $orderRow->discountPercent,
                    $orderRow->quantity, 
                    $orderRow->amountExVat, 
                    $orderRow->unit, 
                    $orderRow->vatPercent
                ),
                SOAP_ENC_OBJECT, null, null, 'OrderRow', "http://schemas.datacontract.org/2004/07/DataObjects.Webservice" 
            );
        }
        
        foreach( $this->orderBuilder->rowsToCredit as $rowToCredit ) {       
            $this->rowNumbers[] = new \SoapVar($rowToCredit, XSD_LONG, null,null, 'long', "http://schemas.microsoft.com/2003/10/Serialization/Arrays");
        }    
        
        $soapRequest = new AdminSoap\CreditInvoiceRowsRequest( 
            new AdminSoap\Authentication( 
                $this->orderBuilder->conf->getUsername( strtoupper($this->orderBuilder->orderType), $this->orderBuilder->countryCode ), 
                $this->orderBuilder->conf->getPassword( strtoupper($this->orderBuilder->orderType), $this->orderBuilder->countryCode ) 
            ),
            $this->orderBuilder->conf->getClientNumber( strtoupper($this->orderBuilder->orderType), $this->orderBuilder->countryCode ),
            $this->orderBuilder->distributionType,
            $this->orderBuilder->invoiceId,
                
            $this->orderRows,
            $this->rowNumbers
        );
        return $soapRequest;
    }
        
    public function validate() {
        $errors = array();
        $errors = $this->validateInvoiceId($errors);
        $errors = $this->validateOrderType($errors);
        $errors = $this->validateCountryCode($errors);
//        $errors = $this->validateRowsToCredit($errors);     
        $errors = $this->validateCreditOrderRowsHasPriceAndVatInformation($errors);
        return $errors;
    }
    
    private function validateInvoiceId($errors) {
        if (isset($this->orderBuilder->invoiceId) == FALSE) {                                                        
            $errors[] = array('missing value' => "invoiceId is required.");
        }
        return $errors;
    }               

    private function validateOrderType($errors) {
        if (isset($this->orderBuilder->orderType) == FALSE) {                                                        
            $errors[] = array('missing value' => "orderType is required.");
        }
        return $errors;
    }            
    
    private function validateCountryCode($errors) {
        if (isset($this->orderBuilder->countryCode) == FALSE) {                                                        
            $errors[] = array('missing value' => "countryCode is required.");
        }
        return $errors;
    }    
    
//    private function validateRowsToCredit($errors) {
//        if (isset($this->orderBuilder->rowsToCredit) == FALSE) {                                                        
//            $errors[] = array('missing value' => "rowsToCredit is required.");
//        }
//        return $errors;
//    }

    private function validateCreditOrderRowsHasPriceAndVatInformation($errors) {
        foreach( $this->orderBuilder->creditOrderRows as $orderRow ) {                                                        
            if( !isset($orderRow->vatPercent) && (!isset($orderRow->amountIncVat) && !isset($orderRow->amountExVat)) ) {            
                $errors[] = array('missing order row vat information' => "cannot calculate orderRow vatPercent, need at least two of amountExVat, amountIncVat and vatPercent.");
            }
        }
        return $errors;
    }
}        
