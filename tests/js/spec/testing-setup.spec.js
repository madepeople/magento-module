"use strict";
/*global describe it expect jasmine loadFixtures jQuery beforeEach */

jasmine.getFixtures().fixturesPath = 'tests/js/fixtures';

describe("Test-setup", function() {

    beforeEach(function() {
        loadFixtures('checkout.html');
    });

    it("has div.payment-methods ", function() {
        expect(jQuery('div.payment-methods')).toHaveClass('payment-methods');
    });

    it("has Payment class object", function() {
        /*global Payment */
        expect(Payment).toBeDefined();
    });

    it("has payment instance", function() {
        /*global payment */
        expect(payment).toBeDefined();
    });

    it("has payment method 'dummy' selected", function() {
        expect(jQuery('input[name="payment[method]"]').val()).toEqual('dummy');
    });

});
