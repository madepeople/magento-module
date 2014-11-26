"use strict";
/*global jasmine jQuery */

/** Helpers for setup of tests
 */

/** Setup a basic checkout
 *
 * Loads checkout fixture and selects a non-svea payment method, 'dummy'.
 */
var initBasicCheckout = function() {
    var fixtures = jasmine.getFixtures();
    fixtures.set(
        [
            '<form method="post" action="http://svea-webpay.testing/onestepcheckout/">',
            /* Payment methods */
            '<dl id="checkout-payment-method-load">',
            fixtures.read('dummy-payment-method.html'),
            fixtures.read('svea-invoice-dt.html'),
            '<dd id="container_payment_method_svea_invoice" class="payment-method">',
            fixtures.read('svea-invoice.html'),
            '</dd>',
            '</dl>',
            /* end Payment methods */
            fixtures.read('billing-address.html'),
            fixtures.read('shipping-address.html'),
            fixtures.read('shipping-methods.html'),
            '</form>',
            fixtures.read('inline-script.html')
        ].join("\n"));
    jQuery('#p_method_dummy').trigger('click');
};

/** Init Svea object with default parameters
 *
 * This should in general only be done once/test.
 *
 * @param checkoutType Checkout type, default 'onepage'
 *
 * @returns The new svea object
 */
var initSvea = function(checkoutType) {
    /*global Svea */
    return new Svea({
        baseUrl: "http://svea-webpay.testing.se/",
        checkoutType: checkoutType || "onepage"
    });
};
