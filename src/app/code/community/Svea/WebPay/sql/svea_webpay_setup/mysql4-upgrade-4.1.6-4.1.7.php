<?php

$installer = $this;
$installer->startSetup();

$sql =<<<SQL
CREATE TABLE IF NOT EXISTS {$this->getTable('svea_webpay_customer_address')} (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `orgnr` VARCHAR(100) NOT NULL,
    `country_code` VARCHAR(2) NOT NULL,
    `address` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SQL;

$installer->run($sql);