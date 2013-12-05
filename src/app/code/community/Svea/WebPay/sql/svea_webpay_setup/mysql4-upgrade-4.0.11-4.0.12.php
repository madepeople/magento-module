<?php
/**
 * Adding these columns is the only core way to make sure that the fees are
 * correctly used and available from the invoice, creditmemo, quote, order, etc
 *
 * It might be possible that we can store things in additional_* fields, but
 * can we really guarantee that serialize() unicodeness works across
 * installations and arbitrary modules? It feels safer to have these columns.
 *
 * @author jonathan@madepeople.se
 */

/** @var Mage_Eav_Model_Entity_Setup */
$installer = $this;

$installer->startSetup();

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