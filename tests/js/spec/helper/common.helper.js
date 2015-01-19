"use strict";
/*global jQuery $ $$ */
/** Common helpers for testing checkout
 */

/** Get billing country element
 *
 * @returns DOMElement
 */
var getBillingCountryElement = function() {
    return jQuery('#billing\\:country_id');
};

/** Get billing country */
var getBillingCountry = function() {
    return getBillingCountryElement().val();
};

/** Set billing country and trigger a change */
var setBillingCountry = function(countryCode) {
    getBillingCountryElement().val(countryCode);
    // Trigger with event.simulate.js
    $$('[name="billing[country_id]"]')[0].simulate('change');
};

/** Get current selected payment method */
var getPaymentMethod = function() {
    return jQuery('input[name="payment[method]"]:checked').val();
};

/** Set current selected payment method by clicking the method radio */
var setPaymentMethod = function(name) {
    jQuery('#p_method_' + name).trigger('click');
};

/** Get radios for invoice customerType */
var getInvoiceCustomerTypeRadio = function() {
    return jQuery(':input[name="payment[svea_invoice][customer_type]"]');
};

/** Get selected invoice customerType */
var getInvoiceCustomerType = function() {
    return getInvoiceCustomerTypeRadio().val();
};

/** Set selected invoice customerType by clicking the correct radio */
var setInvoiceCustomerType = function(type) {
    return getInvoiceCustomerTypeRadio().val(type).trigger('click');
};
