<?php
$root = realpath(dirname(__FILE__));

require_once $root . '/../../../../src/Includes.php';
require_once $root . '/../../../../src/WebService/svea_soap/SveaSoapConfig.php';

/**
 * @author Kristian Grossman-Madsen for Svea Webpay
 */
class ListPaymentMethodsTest extends PHPUnit_Framework_TestCase {
        
    protected $configObject;
    protected $listpaymentmethodsObject;

    // fixture, run once before each test method
    protected function setUp() {
        $this->configObject = Svea\SveaConfig::getDefaultConfig();
        $this->listpaymentmethodObject = new Svea\HostedService\ListPaymentMethods( $this->configObject );
    }

    // test methods
    function test_class_exists(){
        $this->assertInstanceOf( "Svea\HostedService\ListPaymentMethods", $this->listpaymentmethodObject);      
        $this->assertEquals( "getpaymentmethods", PHPUnit_Framework_Assert::readAttribute($this->listpaymentmethodObject, 'method') );        
    }
                  
    function test_prepareRequest_array_contains_mac_merchantid_message() {

        // set up ListPaymentMethods object & get request form
        $countryCode = "SE";
        $this->listpaymentmethodObject->countryCode = $countryCode;   
        
        $form = $this->listpaymentmethodObject->prepareRequest();

        // prepared request is message (base64 encoded), merchantid, mac
        $this->assertTrue( isset($form['merchantid']) );
        $this->assertTrue( isset($form['mac']) );
        $this->assertTrue( isset($form['message']) );
    }
    
    function test_prepareRequest_request_has_correct_merchantid_mac_and_ListPaymentMethods_request_message_contents() {

        $countryCode = "SE";
        $this->listpaymentmethodObject->countryCode = $countryCode;    
        
        $form = $this->listpaymentmethodObject->prepareRequest();
        
        // get our merchantid & secret
        $merchantid = $this->configObject->getMerchantId( ConfigurationProvider::HOSTED_TYPE, $countryCode);   
        $secret = $this->configObject->getSecret( ConfigurationProvider::HOSTED_TYPE, $countryCode);
         
        // check mechantid
        $this->assertEquals( $merchantid, urldecode($form['merchantid']) );

        // check valid mac
        $this->assertEquals( hash("sha512", urldecode($form['message']). $secret), urldecode($form['mac']) );
        
        // check request message contents
        $xmlMessage = new SimpleXMLElement( base64_decode(urldecode($form['message'])) );

        $this->assertEquals( "getpaymentmethods", $xmlMessage->getName() );   // root node        
        $this->assertEquals((string)$merchantid, $xmlMessage->merchantid);        
    }       
}
?>
