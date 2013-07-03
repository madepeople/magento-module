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
");

$installer->endSetup();