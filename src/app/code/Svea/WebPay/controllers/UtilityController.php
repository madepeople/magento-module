<?php

/**
 * This controller contains various utility functions, so I simply called it...
 * UtilityController
 *
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_UtilityController extends Mage_Core_Controller_Front_Action
{
    public function getAddressAction()
    {
        $ssn = $this->getRequest()->getParam('ssn');
        if (empty($ssn)) {
            // Just don't do anything
            return;
        }
    }
}