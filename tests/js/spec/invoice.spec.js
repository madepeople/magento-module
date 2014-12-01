"use strict";
/** Test for invoice */
/*global beforeEach describe it expect jQuery jasmine */
/*global initCustomCheckout initOnepageCheckout initOnestepCheckout initSvea setBillingCountry getBillingCountry getPaymentMethod setPaymentMethod setInvoiceCustomerType getInvoiceCustomerType */

jasmine.getFixtures().fixturesPath = 'tests/js/fixtures';

describe('Svea Invoice customerType radios', function() {
    var individualDiv,
        companyDiv;

    beforeEach(function() {
        initOnepageCheckout();

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

    beforeEach(function() {
        initOnepageCheckout();
    });

    /** Test that checks that an input isn't disabled after svea_invoice + 'SE' is selected
     * @param input Input 'name', like 'firstname'
     */
    function _testDoesNotDisableInput(input) {
        var inputElement = jQuery('#billing\\:' + input);

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(inputElement).not.toBeDisabled();
    }

    it('does not disable billing:firstname', function() {
        _testDoesNotDisableInput('firstname');
    });

    it('does not disable billing:lastname', function() {
        _testDoesNotDisableInput('lastname');
    });

});

describe('Svea Invoice with checkout other than onepage', function() {

    /** Load fixture and select dummy payment method */
    beforeEach(function() {
        initCustomCheckout({checkoutType: 'thirdparty'});
    });

    /** Test that checks that an input is disabled after svea_invoice + 'SE' is selected
     * @param input Input 'name', like 'firstname'
     */
    function _testDisablesInput(input) {
        var inputElement = jQuery('#billing\\:' + input);

        expect(inputElement).not.toBeDisabled();

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(inputElement).toBeDisabled();
    }

    it('disables billing:firstname when svea_invoice and SE is selected', function() {
        _testDisablesInput('firstname');
    });

    it('disables billing:lastname if svea_invoice and SE is selected', function() {
        _testDisablesInput('lastname');
    });

});

describe('Svea Invoice with onepagecheckout', function() {

    beforeEach(function() {
        initOnestepCheckout();
    });

    it('checks use_for_shipping when svea_invoice and SE is selected', function() {
        var useForShipping = jQuery('#billing\\:use_for_shipping_yes');

        expect(useForShipping).toBeChecked();

        useForShipping.trigger('click');
        expect(useForShipping).not.toBeChecked();

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(useForShipping).toBeChecked();

    });

    it('makes use_for_shipping readonly when svea_invoice and SE is selected', function() {
        var useForShipping = jQuery('#billing\\:use_for_shipping_yes');

        expect(useForShipping).not.toHaveAttr('readonly');

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(useForShipping).toHaveAttr('readonly');

    });
});
