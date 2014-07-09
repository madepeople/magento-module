<?php

$root = realpath(dirname(__FILE__));
require_once $root . '/../../../../src/Includes.php';
require_once $root . '/../../../../src/WebService/svea_soap/SveaSoapConfig.php';

/**
 * @author Kristian Grossman-Madsen for Svea Webpay
 */
class RecurTransactionTest extends PHPUnit_Framework_TestCase {
        
    protected $configObject;
    protected $recurTransactionObject;

    // fixture, run once before each test method
    protected function setUp() {
        $this->configObject = Svea\SveaConfig::getDefaultConfig();
        $this->recurTransactionObject = new Svea\HostedService\RecurTransaction($this->configObject);
    }

    // test methods
    function test_class_exists(){
        $this->assertInstanceOf( "Svea\HostedService\RecurTransaction", $this->recurTransactionObject);      
        $this->assertEquals( "recur", PHPUnit_Framework_Assert::readAttribute($this->recurTransactionObject, 'method') );        
    }
    
    function test_setCountryCode(){
        $countryCode = "SE";       
        $this->recurTransactionObject->setCountryCode( $countryCode ); 
        $this->assertEquals( $countryCode, PHPUnit_Framework_Assert::readAttribute($this->recurTransactionObject, 'countryCode') );
    }
    
    function test_setCurrency() {
        $currency = "SEK";
        $this->recurTransactionObject->setCurrency( $currency );
        $this->assertEquals( $currency, PHPUnit_Framework_Assert::readAttribute($this->recurTransactionObject, 'currency') );
    }    
    
    function test_setAmount() {
        $amount = 100;
        $this->recurTransactionObject->setAmount( $amount );
        $this->assertEquals( $amount, PHPUnit_Framework_Assert::readAttribute($this->recurTransactionObject, 'amount') );
    }

    function test_setCustomerRefNo( ){
        $customerRefNo = "myCustomerRefNo";       
        $this->recurTransactionObject->setCustomerRefNo( $customerRefNo );
        $this->assertEquals( $customerRefNo, PHPUnit_Framework_Assert::readAttribute($this->recurTransactionObject, 'customerRefNo') );
    }
    
    function test_setSubscriptionId( ){
        $subscriptionId = 987654;       
        $this->recurTransactionObject->setSubscriptionId( $subscriptionId );
        $this->assertEquals( $subscriptionId, PHPUnit_Framework_Assert::readAttribute($this->recurTransactionObject, 'subscriptionId') );
    }

              
    function test_prepareRequest_array_contains_mac_merchantid_message() {

        // set up recurTransaction object & get request form
        $customerRefNo = "myCustomerRefNo";       
        $this->recurTransactionObject->setCustomerRefNo( $customerRefNo );
        
        $subscriptionId = 987654;       
        $this->recurTransactionObject->setSubscriptionId( $subscriptionId );

        $currency = "SEK";
        $this->recurTransactionObject->setCurrency( $currency );

        $amount = 100;
        $this->recurTransactionObject->setAmount( $amount );
        
        $countryCode = "SE";
        $this->recurTransactionObject->setCountryCode($countryCode);
                
        $form = $this->recurTransactionObject->prepareRequest();

        // prepared request is message (base64 encoded), merchantid, mac
        $this->assertTrue( isset($form['merchantid']) );
        $this->assertTrue( isset($form['mac']) );
        $this->assertTrue( isset($form['message']) );
    }
    
    function test_prepareRequest_has_correct_merchantid_mac_and_lowerTransaction_request_message_contents() {

        // set up recurTransaction object & get request form
        $customerRefNo = "myCustomerRefNo";       
        $this->recurTransactionObject->setCustomerRefNo( $customerRefNo );
        
        $subscriptionId = 987654;       
        $this->recurTransactionObject->setSubscriptionId( $subscriptionId );

        $currency = "SEK";
        $this->recurTransactionObject->setCurrency( $currency );

        $amount = 100;
        $this->recurTransactionObject->setAmount( $amount );
        
        $countryCode = "SE";
        $this->recurTransactionObject->setCountryCode($countryCode);
                
        $form = $this->recurTransactionObject->prepareRequest();

        // get our merchantid & secret
        $merchantid = $this->configObject->getMerchantId( ConfigurationProvider::HOSTED_TYPE, $countryCode);
        $secret = $this->configObject->getSecret( ConfigurationProvider::HOSTED_TYPE, $countryCode);
         
        // check mechantid
        $this->assertEquals( $merchantid, urldecode($form['merchantid']) );

        // check valid mac
        $this->assertEquals( hash("sha512", urldecode($form['message']). $secret), urldecode($form['mac']) );
        
        // check credit request message contents
        $xmlMessage = new SimpleXMLElement( base64_decode(urldecode($form['message'])) );

        $this->assertEquals( "recur", $xmlMessage->getName() );   // root node        
        $this->assertEquals((string)$customerRefNo, $xmlMessage->customerrefno);
        $this->assertEquals((string)$subscriptionId, $xmlMessage->subscriptionid);
        $this->assertEquals((string)$currency, $xmlMessage->currency);   
        $this->assertEquals((string)$amount, $xmlMessage->amount);        
    }
        
    function test_prepareRequest_missing_customerRefNo_throws_exception() {

        $this->setExpectedException(
            'Svea\ValidationException', 
            '-missing value : customerRefNo is required. Use function setCustomerRefNo (also check setClientOrderNumber in order builder).'
        );   
  
        $subscriptionId = 987654;       
        $this->recurTransactionObject->setSubscriptionId( $subscriptionId );

        $currency = "SEK";
        $this->recurTransactionObject->setCurrency( $currency );

        $amount = 100;
        $this->recurTransactionObject->setAmount( $amount );
        
        $countryCode = "SE";
        $this->recurTransactionObject->setCountryCode($countryCode);
                
        $form = $this->recurTransactionObject->prepareRequest();   
    }    
    
    function test_prepareRequest_missing_subscriptionId_throws_exception() {

        $this->setExpectedException(
            'Svea\ValidationException', 
            '-missing value : subscriptionId is required. Use function setSubscriptionId() with the subscriptionId from the createOrder response.'
        );   
    
        $customerRefNo = "myCustomerRefNo";       
        $this->recurTransactionObject->setCustomerRefNo( $customerRefNo );
        
        $currency = "SEK";
        $this->recurTransactionObject->setCurrency( $currency );

        $amount = 100;
        $this->recurTransactionObject->setAmount( $amount );
        
        $countryCode = "SE";
        $this->recurTransactionObject->setCountryCode($countryCode);
                
        $form = $this->recurTransactionObject->prepareRequest();   
    }   
    
 
    function test_prepareRequest_missing_amount_throws_exception() {

        $this->setExpectedException(
            'Svea\ValidationException', 
            '-missing value : amount is required. Use function setAmount().'
        );   
    
        $customerRefNo = "myCustomerRefNo";       
        $this->recurTransactionObject->setCustomerRefNo( $customerRefNo );
        
        $subscriptionId = 987654;       
        $this->recurTransactionObject->setSubscriptionId( $subscriptionId );

        $currency = "SEK";
        $this->recurTransactionObject->setCurrency( $currency );

        $countryCode = "SE";
        $this->recurTransactionObject->setCountryCode($countryCode);
                
        $form = $this->recurTransactionObject->prepareRequest();   
    }   
    
    function test_prepareRequest_missing_currency_does_not_throw_an_exception() {
    
        $customerRefNo = "myCustomerRefNo";       
        $this->recurTransactionObject->setCustomerRefNo( $customerRefNo );
        
        $subscriptionId = 987654;       
        $this->recurTransactionObject->setSubscriptionId( $subscriptionId );

        $amount = 100;
        $this->recurTransactionObject->setAmount( $amount );
        
        $countryCode = "SE";
        $this->recurTransactionObject->setCountryCode($countryCode);
                
        $form = $this->recurTransactionObject->prepareRequest();   
    }     
}
?>
