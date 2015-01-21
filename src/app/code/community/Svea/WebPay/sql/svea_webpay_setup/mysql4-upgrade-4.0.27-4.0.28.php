<?php

$installer = $this;
$installer->startSetup();

// This is how usingQuickCheckout was calculated
$usingQuickCheckout = Mage::getStoreConfigFlag('streamcheckout/general/enabled');

if ($usingQuickCheckout) {
    $displaySsnSelector = 0;
} else {
    $displaySsnSelector = 1;
}

$installer->setConfigData("svea_webpay/general/display_ssn_selector_with_payment_method", $displaySsnSelector);

$installer->setConfigData("svea_webpay/general/lock_required_fields", 1);

$installer->endSetup();