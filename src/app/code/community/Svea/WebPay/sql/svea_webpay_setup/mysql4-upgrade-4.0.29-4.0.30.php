<?php

/**
 * Modify the deliver_method to use the constants defined in the new integration
 * package.
 *
 * @author jonathan@madepeople.se
 */

$installer = $this;
$installer->startSetup();

$installer->run("
UPDATE {$installer->getTable('core_config_data')}
SET `value` = CONCAT(UCASE(LEFT(`value`, 1)),
                     LOWER(SUBSTRING(`value`, 2)))
WHERE `path` = 'payment/svea_invoice/deliver_method';
");

$installer->endSetup();