"use strict";
/** Test for invoice */
/*global beforeEach describe it expect jQuery jasmine */
/*global initBasicCheckout initSvea setBillingCountry getBillingCountry getPaymentMethod setPaymentMethod setInvoiceCustomerType getInvoiceCustomerType */

jasmine.getFixtures().fixturesPath = 'tests/js/fixtures';

describe('Svea Invoice customerType radios', function() {
    var svea,
        individualDiv,
        companyDiv;

    beforeEach(function() {
        initBasicCheckout();
        svea = initSvea('onepage');

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        individualDiv = jQuery('div.svea-type-individual'),
        companyDiv = jQuery('div.svea-type-company');
    });

    it('toggles individual div when changing to and from individual', function() {

        setInvoiceCustomerType('company');
        expect(individualDiv).toBeHidden();

        setInvoiceCustomerType('individual');
        expect(individualDiv).not.toBeHidden();

        setInvoiceCustomerType('company');
        expect(individualDiv).toBeHidden();
    });

    it('toggles company div when changing to and from company', function() {
        setInvoiceCustomerType('individual');
        expect(companyDiv).toBeHidden();

        setInvoiceCustomerType('company');
        expect(companyDiv).not.toBeHidden();

        setInvoiceCustomerType('individual');
        expect(companyDiv).toBeHidden();
    });

});

describe('Svea Invoice with onepage checkout', function() {
    var svea;

    beforeEach(function() {
        initBasicCheckout();
        svea = initSvea('onepage');
    });

    it('does not disable billing:firstname', function() {
        var firstNameInput = jQuery('#billing\\:firstname');

        expect(firstNameInput).not.toBeDisabled();

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(firstNameInput).not.toBeDisabled();
    });

    it('does not disable billing:lastname', function() {
        var firstNameInput = jQuery('#billing\\:lastname');

        expect(firstNameInput).not.toBeDisabled();

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(firstNameInput).not.toBeDisabled();
    });

});

describe('Svea Invoice with checkout other than onepage', function() {
    var svea;

    /** Load fixture and select dummy payment method */
    beforeEach(function() {
        initBasicCheckout();
        svea = initSvea('thirdparty');
    });

    it('disables billing:firstname', function() {
        var firstNameInput = jQuery('#billing\\:firstname');

        expect(firstNameInput).not.toBeDisabled();

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(firstNameInput).toBeDisabled();
    });

    it('disables billing:lastname', function() {
        var firstNameInput = jQuery('#billing\\:lastname');

        expect(firstNameInput).not.toBeDisabled();

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(firstNameInput).toBeDisabled();
    });

});
