"use strict";
/*global loadFixtures jQuery */

/** Helpers for setup of tests
 */

/** Setup a basic checkout
 *
 * Loads checkout fixture and selects a non-svea payment method, 'dummy'.
 */
var initBasicCheckout = function() {
    loadFixtures('checkout.html');
    jQuery('#p_method_dummy').trigger('click');
};
