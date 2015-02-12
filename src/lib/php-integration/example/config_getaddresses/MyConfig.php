<?php
namespace Svea;

class MyConfig {    // changed the class name to MyConfig

    const SWP_TEST_URL = "https://test.sveaekonomi.se/webpay/payment";
    const SWP_PROD_URL = "https://webpay.sveaekonomi.se/webpay/payment";
    const SWP_TEST_WS_URL = "https://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL";
    const SWP_PROD_WS_URL = "https://webservices.sveaekonomi.se/webpay/SveaWebPay.asmx?WSDL";
    const SWP_TEST_HOSTED_ADMIN_URL = "https://test.sveaekonomi.se/webpay/rest/";
    const SWP_PROD_HOSTED_ADMIN_URL = "https://webpay.sveaekonomi.se/webpay/rest/";
    const SWP_TEST_ADMIN_URL = "https://partnerweb.sveaekonomi.se/WebPayAdminService_test/AdminService.svc/backward"; // /backward => SOAP 1.1
    const SWP_PROD_ADMIN_URL = "https://partnerweb.sveaekonomi.se/WebPayAdminService/AdminService.svc/backward"; // /backward => SOAP 1.1
       
    /** 
     * Replace the provided Svea test account credentials with your own to use
     * the package with your own account.
     * 
     * @return \Svea\SveaConfigurationProvider
     */
    public static function getTestConfig() {
        $testConfig = array();

        
        // test credentials for Sweden
        $testConfig["SE"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE => 
                    array(
                        "username" => "sverigetest", // swap this for your actual SE invoice test account credentials
                        "password" => "sverigetest", // swap this for your actual SE invoice test account credentials
                        "clientNumber" => 79021 // swap this for your actual SE invoice test account credentials
                    ),

                    \ConfigurationProvider::PAYMENTPLAN_TYPE => 
                    array(
                        "username" => "sverigetest", // swap this for your actual SE payment plan test account credentials
                        "password" => "sverigetest", // swap this for your actual SE payment plan test account credentials
                        "clientNumber" => 59999 // swap this for your actual SE payment plan test account credentials
                    ),

                    \ConfigurationProvider::HOSTED_TYPE => 
                    array(
                        // swap these for your actual merchant id and secret word
                        "merchantId" => 1130, 
                        "secret" => "8a9cece566e808da63c6f07ff415ff9e127909d000d259aba24daa2fed6d9e3f8b0b62e8ad1fa91c7d7cd6fc3352deaae66cdb533123edf127ad7d1f4c77e7a3"
                    )
                )
            )
        ;
        
        // We don't accept payment plan payments in Norway, nor do we accept card payments from there
        $testConfig["NO"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE => 
                    array(
                        "username" => "norgetest2", // swap this for your actual SE invoice account credentials
                        "password" => "norgetest2", // swap this for your actual SE invoice account credentials
                        "clientNumber" => 33308 // swap this for your actual SE invoice account credentials
                    ),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::HOSTED_TYPE      => array("merchantId" => "", "secret"   => "")
                )
            )
        ;
        
        // We have no invoice or payment plan accounts for Denmark, but our card payment account is configured to accept orders there
        $testConfig["DK"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE     => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    array(
                        // swap these for your actual merchant id and secret word
                        "merchantId" => 1130,
                        "secret" => "8a9cece566e808da63c6f07ff415ff9e127909d000d259aba24daa2fed6d9e3f8b0b62e8ad1fa91c7d7cd6fc3352deaae66cdb533123edf127ad7d1f4c77e7a3"                        
                    ) 
                )
            )
        ;
        
        // We have no invoice or payment plan accounts for Finland, neither do we accept card payments from there
        $testConfig["FI"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE     => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::HOSTED_TYPE      => array("merchantId" => "", "secret"   => "")
                )
            )
        ;
        
        // We have no invoice or payment plan accounts for Germany, neither do we accept card payments from there
        $testConfig["DE"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE     => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::HOSTED_TYPE      => array("merchantId" => "", "secret"   => "")
                )
            )
        ;
        
        // We have no invoice or payment plan accounts for Netherlands, neither do we accept card payments from Denmark
        $testConfig["NL"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE     => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::HOSTED_TYPE      => array("merchantId" => "", "secret"   => "")
                )
            )
        ;
        
        // don't modify this
        $url =             array(
                                \ConfigurationProvider::HOSTED_TYPE      => self::SWP_TEST_URL,
                                \ConfigurationProvider::INVOICE_TYPE     => self::SWP_TEST_WS_URL,
                                \ConfigurationProvider::PAYMENTPLAN_TYPE => self::SWP_TEST_WS_URL,
                                \ConfigurationProvider::HOSTED_ADMIN_TYPE => self::SWP_TEST_HOSTED_ADMIN_URL,
                                \ConfigurationProvider::ADMIN_TYPE       => self::SWP_TEST_ADMIN_URL
                            );

        return new SveaConfigurationProvider(array("url" => $url, "credentials" => $testConfig));
    }
    
    public static function getProdConfig() {
        $prodConfig = array();

        $prodConfig["SE"] = 
            array("auth" =>                
                array(           
                    // invoice payment method credentials for SE, i.e. client number, username and password
                    // replace with your own, or leave blank
                    \ConfigurationProvider::INVOICE_TYPE => 
                    array(
                        "username" => "sverigetest", // swap this for your actual SE invoice prod account credentials
                        "password" => "sverigetest", // swap this for your actual SE invoice prod account credentials
                        "clientNumber" => 79021 // swap this for your actual SE invoice prod account credentials
                    ),

                    // payment plan payment method credentials for SE
                    // replace with your own, or leave blank
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => 
                    array(
                        "username" => "sverigetest", // swap this for your actual SE payment plan prod account credentials
                        "password" => "sverigetest", // swap this for your actual SE payment plan prod account credentials
                        "clientNumber" => 59999 // swap this for your actual SE payment plan prod account credentials
                    ),

                    // card and direct bank payment method credentials, i.e. merchant id and secret word
                    // replace with your own, or leave blank
                    \ConfigurationProvider::HOSTED_TYPE => 
                    array(
                        "merchantId" => 1130, 
                        "secret" => "8a9cece566e808da63c6f07ff415ff9e127909d000d259aba24daa2fed6d9e3f8b0b62e8ad1fa91c7d7cd6fc3352deaae66cdb533123edf127ad7d1f4c77e7a3"
                    )
                )
            )
        ;
        
        // We don't accept payment plan payments in Norway, nor do we accept card payments from there
        $prodConfig["NO"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE => 
                    array(
                        "username" => "norgetest2", // swap this for your actual SE invoice account credentials
                        "password" => "norgetest2", // swap this for your actual SE invoice account credentials
                        "clientNumber" => 33308 // swap this for your actual SE invoice account credentials
                    ),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::HOSTED_TYPE      => array("merchantId" => "", "secret"   => "")
                )
            )
        ;
        
        // We have no invoice or payment plan accounts for Denmark, but our card payment account is configured to accept orders there
        $prodConfig["DK"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE     => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    array(
                        // swap these for your actual merchant id and secret word
                        "merchantId" => 1130,
                        "secret" => "8a9cece566e808da63c6f07ff415ff9e127909d000d259aba24daa2fed6d9e3f8b0b62e8ad1fa91c7d7cd6fc3352deaae66cdb533123edf127ad7d1f4c77e7a3"                        
                    ) 
                )
            )
        ;

        // We have no invoice or payment plan accounts for Finland, neither do we accept card payments from there
        $prodConfig["FI"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE     => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::HOSTED_TYPE      => array("merchantId" => "", "secret"   => "")
                )
            )
        ;
        // We have no invoice or payment plan accounts for Germany, neither do we accept card payments from there
        $prodConfig["DE"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE     => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::HOSTED_TYPE      => array("merchantId" => "", "secret"   => "")
                )
            )
        ;
        // We have no invoice or payment plan accounts for Netherlands, neither do we accept card payments from Denmark
        $prodConfig["NL"] = 
            array("auth" =>                
                array(           
                    \ConfigurationProvider::INVOICE_TYPE     => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::PAYMENTPLAN_TYPE => array("username"   => "", "password" => "", "clientNumber" => ""),
                    \ConfigurationProvider::HOSTED_TYPE      => array("merchantId" => "", "secret"   => "")
                )
            )
        ;

        // don't modify this
        $url =              array(
                                \ConfigurationProvider::HOSTED_TYPE      => self::SWP_PROD_URL,
                                \ConfigurationProvider::INVOICE_TYPE     => self::SWP_PROD_WS_URL,
                                \ConfigurationProvider::PAYMENTPLAN_TYPE => self::SWP_PROD_WS_URL,
                                \ConfigurationProvider::HOSTED_ADMIN_TYPE => self::SWP_PROD_HOSTED_ADMIN_URL,
                                \ConfigurationProvider::ADMIN_TYPE       => self::SWP_PROD_ADMIN_URL
                            );

        return new SveaConfigurationProvider(array("url" => $url, "credentials" => $prodConfig));
    }
    
    /**
     * @return \Svea\SveaConfigurationProvider 
     */
    public static function getDefaultConfig() {
        return self::getTestConfig();
    }
}
