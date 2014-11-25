"use strict";
/*global describe it expect jasmine loadFixtures jQuery beforeEach */
/*global setBillingCountry getBillingCountry getPaymentMethod setPaymentMethod */

jasmine.getFixtures().fixturesPath = 'tests/js/fixtures';

describe("Test-setup", function() {

    /** Load fixture and select dummy payment method */
    beforeEach(function() {
        initBasicCheckout();
    });

    it("has helper functions", function() {
        /*global initBasicCheckout */
        expect(jQuery('div.payment-methods')).toHaveClass('payment-methods');
    });

    it("has payment method 'dummy' selected", function() {
        expect(getPaymentMethod()).toEqual('dummy');
    });

    it("can select payment method 'svea_invoice'", function() {
        expect(getPaymentMethod()).toEqual('dummy');
        setPaymentMethod('svea_invoice');
        expect(getPaymentMethod()).toEqual('svea_invoice');
    });

    it("has billing address country 'SE'", function() {
        expect(getBillingCountry()).toEqual('SE');
    });

    it("can change billing address country", function() {
        expect(getBillingCountry()).toEqual('SE');
        setBillingCountry('AL');
        expect(getBillingCountry()).toEqual('AL');
    });

});
