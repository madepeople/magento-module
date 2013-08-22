<?php

/** @var Mage_Eav_Model_Entity_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS {$this->getTable('svea_webpay_paymentplan')} (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `campaincode` VARCHAR( 100 ) NOT NULL,
    `description` VARCHAR( 100 ) NOT NULL ,
    `paymentplantype` VARCHAR( 100 ) NOT NULL ,
    `contractlength` INT NOT NULL ,
    `monthlyannuityfactor` DOUBLE NOT NULL ,
    `initialfee` DOUBLE NOT NULL ,
    `notificationfee` DOUBLE NOT NULL ,
    `interestratepercentage` INT NOT NULL ,
    `interestfreemonths` INT NOT NULL ,
    `paymentfreemonths` INT NOT NULL ,
    `fromamount` DOUBLE NOT NULL ,
    `toamount` DOUBLE NOT NULL ,
    `timestamp` INT UNSIGNED NOT NULL,
    `storeid` INT NOT NULL
);

UPDATE {$this->getTable('sales_flat_order_payment')} SET method = 'svea_paymentplan' WHERE method = 'swpwspartpay';
UPDATE {$this->getTable('sales_flat_order_payment')} SET method = 'svea_invoice' WHERE method = 'swpwsinvoice';
UPDATE {$this->getTable('sales_flat_order_payment')} SET method = 'svea_cardpayment' WHERE method = 'swphgcard';
UPDATE {$this->getTable('sales_flat_order_payment')} SET method = 'svea_directpayment' WHERE method = 'swphgall';
");


//Deactivate older modules

$common = Mage::getRoot() . "/etc/modules/SveaWebPay_Common.xml";
$hosted = Mage::getRoot() . "/etc/modules/SveaWebPay_Hosted.xml";
$hostedg = Mage::getRoot() . "/etc/modules/SveaWebPay_HostedG.xml";
$webservice = Mage::getRoot() . "/etc/modules/SveaWebPay_Webservice.xml";

if (file_exists($common)) {
    $xml = simplexml_load_file($common);
    $xml->modules[0]->SveaWebPay_Common[0]->active = 'false';
    $output = $xml->asXML($common);
}

if (file_exists($hosted)) {
    $xml = simplexml_load_file($hosted);
    $xml->modules[0]->SveaWebPay_Hosted[0]->active = 'false';
    $output = $xml->asXML($hosted);
}

if (file_exists($hostedg)) {
    $xml = simplexml_load_file($hostedg);
    $xml->modules[0]->SveaWebPay_HostedG[0]->active = 'false';
    $output = $xml->asXML($hostedg);
}

if (file_exists($webservice)) {
    $xml = simplexml_load_file($webservice);
    $xml->modules[0]->SveaWebPay_Webservice[0]->active = 'false';
    $output = $xml->asXML($webservice);
}

$installer->endSetup();