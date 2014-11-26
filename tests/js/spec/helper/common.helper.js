"use strict";
/*global jQuery */
/** Common helpers for testing checkout
 */

/** Get billing country element */
var getBillingCountryElement = function() {
    return jQuery('#billing\\:country_id');
};

/** Get billing country */
var getBillingCountry = function() {
    return getBillingCountryElement().val();
};

/** Set billing country */
var setBillingCountry = function(countryCode) {
    getBillingCountryElement().val(countryCode).trigger('change');
};

/** Get current selected payment method */
var getPaymentMethod = function() {
    return jQuery('input[name="payment[method]"]:checked').val();
};

/** Set current selected payment method */
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
