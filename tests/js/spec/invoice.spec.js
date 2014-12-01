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
    var svea;

    beforeEach(function() {
        svea = initOnepageCheckout();
    });

    /** Test that checks that an input isn't set to readonly after svea_invoice + 'SE' is selected
     * @param input Input 'name', like 'firstname'
     */
    function _testDoesNotSetInputToReadonly(input) {
        var inputElement = jQuery('#billing\\:' + input);

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(inputElement).not.toHaveAttr('readonly');
    }

    it('does not set billing:firstname to readonly', function() {
        _testDoesNotSetInputToReadonly('firstname');
    });

    it('does not set billing:lastname to readonly', function() {
        _testDoesNotSetInputToReadonly('lastname');
    });

    it('displays #svea-invoice-payment-not-available when selecting US', function() {
        var div = jQuery('#svea-invoice-payment-not-available');

        setPaymentMethod('svea_invoice');

        setBillingCountry('SE');

        expect(div).toBeHidden();

        setBillingCountry('US');

        expect(div).not.toBeHidden();

    });

    it('hides #svea-invoice-payment-not-available when going from US to SE', function() {
        var div = jQuery('#svea-invoice-payment-not-available');

        setPaymentMethod('svea_invoice');
        setBillingCountry('US');

        expect(div).not.toBeHidden();

        setBillingCountry('SE');
        expect(div).toBeHidden();

    });

    it('hides #svea-invoice-payment-information when selecting US', function() {
        var div = jQuery('#svea-invoice-payment-information');

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(div).not.toBeHidden();

        setBillingCountry('US');
        expect(div).toBeHidden();

    });

    it('displays #svea-invoice-payment-information when going from US to SE', function() {
        var div = jQuery('#svea-invoice-payment-information');

        setPaymentMethod('svea_invoice');
        setBillingCountry('US');

        expect(div).toBeHidden();

        setBillingCountry('SE');
        expect(div).not.toBeHidden();

    });

    it('reads correct billing country code', function() {
        setBillingCountry('US');
        expect(svea.getBillingCountry()).toEqual('US');
        setBillingCountry('SE');
        expect(svea.getBillingCountry()).toEqual('SE');
    });

});

describe('Svea Invoice with checkout other than onepage', function() {

    /** Load fixture and select dummy payment method */
    beforeEach(function() {
        initCustomCheckout({checkoutType: 'thirdparty'});
    });

    /** Test that checks that an input is set to readonly after svea_invoice + 'SE' is selected
     * @param input Input 'name', like 'firstname'
     */
    function _testSetsInputToReadonly(input) {
        var inputElement = jQuery('#billing\\:' + input);

        expect(inputElement).not.toHaveAttr('readonly');

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(inputElement).toHaveAttr('readonly');
    }

    it('sets billing:firstname to readonly when svea_invoice and SE is selected', function() {
        _testSetsInputToReadonly('firstname');
    });

    it('sets billing:lastname to readonly when svea_invoice and SE is selected', function() {
        _testSetsInputToReadonly('lastname');
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

    it('adds class svea-hidden to use_for_shipping_yes when svea_invoice and SE is selected', function() {
        var useForShipping = jQuery('#billing\\:use_for_shipping_yes');

        expect(useForShipping).not.toHaveClass('svea-hidden');

        setPaymentMethod('svea_invoice');
        setBillingCountry('SE');

        expect(useForShipping).toHaveClass('svea-hidden');

    });

});
