"use strict";
/*global jasmine jQuery */

/** Helpers for setup of tests
 */

/** Setup a checkout fixture
 *
 * Loads checkout fixture.
 *
 * @param extraFormFixtures List of fixtures that should be added within <form>
 */
var setupCheckoutFixture = function(extraFormFixtures) {
    var fixtures = jasmine.getFixtures(),
        extraContent = [];

    jQuery.each(extraFormFixtures || [], function(idx, path) {
        extraContent.push(fixtures.read(path));
    });

    fixtures.set(
        [
            '<form method="post" action="http://svea-webpay.testing/checkout/">',
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
        ].concat(extraContent).concat([
            '</form>',
            fixtures.read('inline-script.html')
        ]).join("\n"));

};

/** Setup a custom checkout
 *
 * Loads checkout fixture, Svea object and selects a non-svea payment
 * method, 'dummy'.
 *
 * @param checkoutOptions Object with checkout options or null
 * @param extraFormFixtures List of extra for fixtures or null
 *
 * @returns Svea object
 */
var initCustomCheckout = function(checkoutOptions, extraFormFixtures) {
    setupCheckoutFixture(extraFormFixtures || []);
    jQuery('#p_method_dummy').trigger('click');
    return initSvea(checkoutOptions || {});
};

/** Setup a onepage checkout
 *
 * Loads onepage checkout fixture, Svea object and selects a non-svea payment
 * method, 'dummy'.
 *
 * @returns Svea object
 */
var initOnepageCheckout = function() {
    return initCustomCheckout({checkoutType: 'onepage'}, ['onepagecheckout.html']);
};

/** Setup Onestep checkout
 */
var initOnestepCheckout = function() {
    return initCustomCheckout({checkoutType: 'onestepcheckout'}, ['onestepcheckout.html']);
};

/** Init Svea object
 *
 * This should in general only be done once/test.
 *
 * @param options Object with:
 * - checkoutType Checkout type, default 'onepage'
 *
 * @returns The new Svea object
 */
var initSvea = function(options) {
    options = jQuery.extend({},
                           {
                               checkoutType: 'onepage',
                               baseUrl: "http://svea-webpay.testing.se/"
                           },
                           options || {});
    /*global Svea */
    return new Svea(options);
};
