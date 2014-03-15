<?php
/**
 * This file is used for new installations in contrary with the upgrade files.
 *
 * We set up the payment plan storage table, deactivate old modules and modify
 * the order related tables to corretly store the payment fee on store view +
 * currency/base currency level
 *
 * @author jonathan@madepeople.se
 */

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

/**
 * Automatically deactivate the really old version of this module. We also
 * replace the existing payment method codes for the old module with the new
 * one. This means they can still be managed in sales/order_view even though
 * the old module has been disabled
 */
$installer->run("
UPDATE {$this->getTable('sales_flat_order_payment')} SET method = 'svea_paymentplan' WHERE method = 'swpwspartpay';
UPDATE {$this->getTable('sales_flat_order_payment')} SET method = 'svea_invoice' WHERE method = 'swpwsinvoice';
UPDATE {$this->getTable('sales_flat_order_payment')} SET method = 'svea_cardpayment' WHERE method = 'swphgcard';
UPDATE {$this->getTable('sales_flat_order_payment')} SET method = 'svea_directpayment' WHERE method = 'swphgall';
");

foreach (array('Common', 'Hosted', 'HostedG', 'Webservice') as $suffix) {
    $filename = Mage::getRoot() . '/etc/modules/SveaWebPay_' . $suffix . '.xml';
    if (file_exists($filename)) {
        $xml = simplexml_load_file($filename);
        $tagname = 'SveaWebPay_' . $suffix;
        $xml->modules[0]->{$tagname}[0]->active = 'false';
        $xml->asXML($filename);
    }
}

/**
 * Adding these columns is the only core way to make sure that the fees are
 * correctly used and available from the invoice, creditmemo, quote, order, etc
 *
 * It might be possible that we can store things in additional_* fields, but
 * can we really guarantee that serialize() unicodeness works across
 * installations and arbitrary modules? It feels safer to have these columns.
 */
$installer->run("
ALTER TABLE {$this->getTable('sales/order')} ADD svea_payment_fee_amount decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Amount';
ALTER TABLE {$this->getTable('sales/order')} ADD svea_payment_fee_canceled decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Canceled';
ALTER TABLE {$this->getTable('sales/order')} ADD svea_payment_fee_invoiced decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Invoiced';
ALTER TABLE {$this->getTable('sales/order')} ADD svea_payment_fee_refunded decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Refunded';
ALTER TABLE {$this->getTable('sales/order')} ADD svea_payment_fee_tax_amount decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Tax Amount';
ALTER TABLE {$this->getTable('sales/order')} ADD svea_payment_fee_tax_refunded decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Tax Refunded';
ALTER TABLE {$this->getTable('sales/order')} ADD svea_payment_fee_incl_tax decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Incl Tax';
ALTER TABLE {$this->getTable('sales/order')} ADD base_svea_payment_fee_amount decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Amount';
ALTER TABLE {$this->getTable('sales/order')} ADD base_svea_payment_fee_canceled decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Canceled';
ALTER TABLE {$this->getTable('sales/order')} ADD base_svea_payment_fee_invoiced decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Invoiced';
ALTER TABLE {$this->getTable('sales/order')} ADD base_svea_payment_fee_refunded decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Refunded';
ALTER TABLE {$this->getTable('sales/order')} ADD base_svea_payment_fee_tax_amount decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Tax Amount';
ALTER TABLE {$this->getTable('sales/order')} ADD base_svea_payment_fee_tax_refunded decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Tax Refunded';
ALTER TABLE {$this->getTable('sales/order')} ADD base_svea_payment_fee_incl_tax decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Incl Tax';

ALTER TABLE {$this->getTable('sales/invoice')} ADD svea_payment_fee_amount decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Amount';
ALTER TABLE {$this->getTable('sales/invoice')} ADD svea_payment_fee_tax_amount decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Tax Amount';
ALTER TABLE {$this->getTable('sales/invoice')} ADD svea_payment_fee_incl_tax decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Incl Tax';
ALTER TABLE {$this->getTable('sales/invoice')} ADD base_svea_payment_fee_amount decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Amount';
ALTER TABLE {$this->getTable('sales/invoice')} ADD base_svea_payment_fee_tax_amount decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Tax Amount';
ALTER TABLE {$this->getTable('sales/invoice')} ADD base_svea_payment_fee_incl_tax decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Incl Tax';

ALTER TABLE {$this->getTable('sales/creditmemo')} ADD svea_payment_fee_amount decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Amount';
ALTER TABLE {$this->getTable('sales/creditmemo')} ADD svea_payment_fee_tax_amount decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Tax Amount';
ALTER TABLE {$this->getTable('sales/creditmemo')} ADD svea_payment_fee_incl_tax decimal(12,4) DEFAULT NULL COMMENT 'Svea Payment Fee Incl Tax';
ALTER TABLE {$this->getTable('sales/creditmemo')} ADD base_svea_payment_fee_amount decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Amount';
ALTER TABLE {$this->getTable('sales/creditmemo')} ADD base_svea_payment_fee_tax_amount decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Tax Amount';
ALTER TABLE {$this->getTable('sales/creditmemo')} ADD base_svea_payment_fee_incl_tax decimal(12,4) DEFAULT NULL COMMENT 'Base Svea Payment Fee Incl Tax';
");

$installer->endSetup();