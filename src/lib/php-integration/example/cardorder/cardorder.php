<?php
/**
 * example file, how to create a card order request
 * 
 * @author Kristian Grossman-madsen for Svea WebPay
 */
error_reporting( E_ALL );
ini_set('display_errors', 'On');

// Include Svea PHP integration package.
$svea_directory = "../../src/";
require_once( $svea_directory . "Includes.php" );

// get config object
$myConfig = Svea\SveaConfig::getTestConfig(); //replace with class holding your merchantid, secretword, et al, adopted from package Config/SveaConfig.php

// We assume that you've collected the following information about the order in your shop:

// customer information:
$customerFirstName = "Tess T";
$customerLastName = "Persson";
$customerAddress = "Testgatan 1";
$customerZipCode = "99999";
$customerCity = "Stan";
$customerCountry = "Sverige";

// The customer has bought three items, one "Billy" which cost 700,99 kr excluding vat (25%) and two hotdogs for 5 kr (incl. vat).

// We'll also need information about the customer country, and the currency used for this order, etc., see below

// Start the order creation process by creating the order builder object by calling WebPay::createOrder():
$myOrder = WebPay::createOrder( $myConfig );

// You then add information to the order object by using the methods in the Svea\CreateOrderBuilder class.
// For a Card order, the following methods are required:
$myOrder->setCountryCode("SE");                         // customer country, we recommend basing this on the customer billing address
$myOrder->setCurrency("SEK");                           // order currency
$myOrder->setClientOrderNumber( "order #".date('c') );  // required - use a not previously sent client side order identifier, i.e. "order #20140519-371"

// You may also chain fluent methods together:
$myOrder
        ->setCustomerReference("customer #123")         // optional - This should contain a customer reference, as in "customer #123".
        ->setOrderDate("2014-05-28")                    // optional - or use an ISO801 date as produced by i.e. date('c')
;

// Then specify the items bought as order rows, using the methods in the Svea\OrderRow class, and adding them to the order:
$firstBoughtItem = WebPayItem::orderRow();
$firstBoughtItem->setAmountExVat( 10.99 );
$firstBoughtItem->setVatPercent( 25 );
$firstBoughtItem->setQuantity( 1 );
$firstBoughtItem->setDescription( "Billy" );
$firstBoughtItem->setArticleNumber("123456789A");

// Add firstBoughtItem to order row
$myOrder->addOrderRow( $firstBoughtItem ); 

// Add secondBoughtItem in a fluent fashion 
$myOrder->addOrderRow( 
            WebPayItem::orderRow()
                ->setAmountIncVat( 5.00 )
                ->setVatPercent( 12 )
                ->setQuantity( 2 )
                ->setDescription( "Korv med bröd" )
);

// For card orders the ->addCustomerDetails() method is optional, but recommended, so we'll add what info we have
$myCustomerInformation = WebPayItem::individualCustomer(); // there's also a ::companyCustomer() method, used for non-person entities

// Set customer information, using the methods from the IndividualCustomer class
$myCustomerInformation->setName( $customerFirstName, $customerLastName);
$sveaAddress = Svea\Helper::splitStreetAddress($customerAddress); // Svea requires an address and a house number
$myCustomerInformation->setStreetAddress( $sveaAddress[0], $sveaAddress[1] );
$myCustomerInformation->setZipCode( $customerZipCode )->setLocality( $customerCity );

$myOrder->addCustomerDetails( $myCustomerInformation );

// We have now completed specifying the order, and wish to send the payment request to Svea. To do so, we first select a payment method.
// For card orders, we recommend using the ->usePaymentMethod(PaymentMethod::KORTCERT), which processes card orders via Certitrade.
$myCardOrderRequest = $myOrder->usePaymentMethod(PaymentMethod::KORTCERT);


// Then set any additional required request attributes as detailed below. (See Svea\PaymentMethodPayment and Svea\HostedPayment classes for details.)
$myCardOrderRequest
    ->setCardPageLanguage("SV")                                     // ISO639 language code, i.e. "SV", "EN" etc. Defaults to English.
    ->setReturnUrl("http://localhost/".getPath()."/landingpage.php"); // The return url where we receive and process the finished request response
      
// Get a payment form object which you can use to send the payment request to Svea
$myCardOrderPaymentForm = $myCardOrderRequest->getPaymentForm();

// Then send the form to Svea, and receive the response on the landingpage after the customer has completed the card checkout at certitrade
echo "<pre>";
print_r( "press submit to send the card payment request to Svea");
print_r( $myCardOrderPaymentForm->completeHtmlFormWithSubmitButton );

/**
 * get the path to this file, for use in specifying the returnurl etc.
 */
function getPath() {
    $myURL = $_SERVER['SCRIPT_NAME'];
    $myPath = explode('/', $myURL);
    unset( $myPath[count($myPath)-1]);
    $myPath = implode( '/', $myPath);

    return $myPath;
}

?>
