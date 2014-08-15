/*global Class $ $$ payment currentCountry $F $H Ajax */
/** Svea magento module javascript part
 *
 * This module takes care of retrieving addresses from svea and modifying the gui
 * so that inputs are hidden/shown enabled or disabled depending on payment
 * method and billing country.
 *
 */

/* Get nationalIdNumber container for a specific payment method
 *
 * @param paymentMethodCode Code for the payment method which has the ssn selector
 *
 * @returns Element or null. null should not happen if the dom is correct
 */
function _sveaGetSsnContainer(paymentMethodCode) {
    var elements,
        container;

    paymentMethodCode = paymentMethodCode || _sveaGetPaymentMethodCode();

    if (typeof paymentMethodCode !== 'undefined' && paymentMethodCode !== '') {
        elements = $$('.svea-ssn-container-' + paymentMethodCode);
        if (elements.length) {
            return elements[0];
        }
    }

    elements = $$('[class*=svea-ssn-container-]');
    if (elements.length) {
        return elements[0];
    } else {
        elements = $$('.svea-ssn-container');
        if (elements.length) {
            return elements[0];
        }
    }

    // console.warn("Cannot find ssn container for payment", paymentMethodCode);
    return null;
}

/** Get element relative a svea ssn container for a specific payment method
 *
 * @param selector What to select under the top element
 * @param paymentMethodCode Code for the payment method that this element should belong to, see `_sveaGetSsnContainer`.
 *
 * @returns Element or null
 */
function _$(selector, paymentMethodCode)
{
    var ssnContainer = _sveaGetSsnContainer(paymentMethodCode);
    if (ssnContainer) {
        return ssnContainer.down(selector);
    } else {
        // console.warn("Cannot find ssn container sub-element for paymentMethodCode", paymentMethodCode, selector);
        return null;
    }
}

/** Get selected SVEA customer type("0" or "1") for a specific payment method
 *
 * @param paymentMethodCode Code for the payment method that the customer type is selected on
 *
 * @returns customer type string id ("0" or "1") or null
 */
function _sveaGetCustomerType(paymentMethodCode) {

    var selector = 'input[name="payment[svea_info][svea_customerType]"]',
        typeElement = _$(selector,
                         paymentMethodCode || _sveaGetPaymentMethodCode());


    if (typeElement === null) {
        typeElement = $$(selector);
        if (typeElement.length ) {
            return typeElement.value;
        } else {
            // console.warn('Failed to get svea customer type for paymentMethod', paymentMethodCode);
            return null;
        }
    } else {
        return typeElement.value;
    }
}

/** Get selected payment method code
 *
 * @returns Element or null
 */
function _sveaGetPaymentMethodCode() {
    var elem = $$('input:checked[name*=payment[method]]');

    if (elem.length) {
        return elem[0].value;
    } else {
        // console.warn("Cannot find payment method");
        return null;
    }
}

/** Get country code for selected billing country
 *
 * `currentCountry` is populated after country is changed so use this to get
 * the current selected billing country code from the billing country select
 * directly.
 *
 * @returns Billing country code or null
 */
function _sveaGetBillingCountryCode() {
    var elem = $$('select[name="billing[country_id]"]');

    if (elem.length) {
        return elem[0].value;
    } else {
        // console.warn("Cannot find country_id");
        return null;
    }
}

/** Get NationalIdNumber
 *
 * @param paymentMethodCode If set that payment method code will be used instead of the current selected payment methods code
 *
 * @returns NationalIdNumber or null
 */
function _sveaGetBillingNationalIdNumber(paymentMethodCode) {
    var elem;

    paymentMethodCode = paymentMethodCode ||_sveaGetPaymentMethodCode();
    if (paymentMethodCode === null) {
        // console.warn("Cannot find current payment method");
        return null;
    }

    elem = _$('[name*=[svea_ssn]]', paymentMethodCode);
    if (elem) {
        return elem.value;
    } else {
        elem = $$('.svea-ssn-input');
        if (elem.length) {
            return elem[0].value;
        } else {
            // console.warn("Cannot find svea_ssn for method", paymentMethodCode);
            return null;
        }
    }
}

/** Get array of elements that are considered read-only when svea is used
 *
 * These elements should be readonly when svea getaddress is required.
 *
 * @returns Array of elements
 */
function _sveaGetReadOnlyElements()
{
    var readOnlyElements = [
            'firstname',
            'lastname',
            'street1',
            'city',
            'postcode'
        ];

    readOnlyElements.each(function (item, index) {
        var id = 'billing:' + item,
            $id = $(id);

        if ($id) {
            readOnlyElements[index] = $id;
        }
    });

    return readOnlyElements;
}

/** A customer read from SVEA
 *
 * This customer may be invalid, check the attribute `valid` to see if it is.
 */
var _SveaCustomer = Class.create({

    valid: null,

    countryCode: null,
    customerType: null,
    nationalIdNumber: null,
    selectedAddressId: null,

    addresses: null,

    response: null,

    initialize: function(config, response) {
        /** Create a new Svea customer returned from svea getAddress
         *
         * @param config Object with countryCode, customerType and nationalIdNumber set
         * @param response Response from getAddress
         */
        var i, l;

        this.countryCode = config.countryCode;
        this.customerType = config.customerType;
        this.nationalIdNumber = config.nationalIdNumber;
        this.selectedAddressId = null;
        this.addresses = {};

        if (this.countryCode === null ||
            this.customerType === null ||
            this.nationalIdNumber === null) {
            /** This is invalid - set all to null to provide the same hash */

            this.countryCode = this.customerType = this.nationalIdNumber = null;

            this.response = null;
            this.valid = false;

        } else {

            this.response = response;
            this.valid = (this.response || {}).accepted || false;

            if (this.valid) {
                // Only valid customers has addresses and selectedAddressId
                l = (response.customerIdentity || []).length;
                if (l > 0) {
                    // Assign addresses
                    for (i = 0; i < l; i++) {
                        this.addresses[response.customerIdentity[i].addressSelector] = response.customerIdentity[i];
                    }
                    // Set selectedAddressId to first address id
                    this.selectedAddressId = response.customerIdentity[0].addressSelector;
                }
            } else {
                // No additional setup required for invalid addresses
            }
        }
    },
    /** Get hash for this Customer
     *
     * @returns String
     */
    getHash: function() {
        return [
            this.countryCode,
            this.customerType,
            this.nationalIdNumber
            ].join("/");
    },
    /** Get selected address
     *
     * If no address is selected or a missing address is selected an empty
     * address object will be returned, with fullName and addressSelector set to
     * null. Other entries will be set to an empty string, ''.
     *
     * @returns Address object
     */
    getSelectedAddress: function() {
        return this.addresses[this.selectedAddressId] || {
            addressSelector: null,
            fullName: null,
            firstName: '',
            lastName: '',
            street: '',
            locality: '',
            zipCode: ''
        };
    },
    /** Check if an address with a specific id exists
     *
     * @param id Address id
     *
     * @returns Boolean
     */
    hasAddress: function(id) {
        return Object.keys(this.addresses).indexOf(id) !== -1;
    },
    /** Set selected address id
     *
     * If `id` is valid and this address is currently selected the gui will be
     * updated. If the id isn't valid nothing will happen.
     *
     * @param id Address id
     *
     * @returns undefined
     */
    setSelectedAddressId: function(id) {

        if (this.hasAddress(id)) {
            this.selectedAddressId = id;
            this.updateSelectedAddressGui();
        }

    },
    /** Update the gui for selecting a specific address
     *
     * This also updates the address values from selected address. No check is done
     * to see if this customer actually is the one that should update the gui.
     *
     * @returns undefined
     */
    updateSelectedAddressGui: function() {
        var selectedAddress = null,
            paymentMethodCode = _sveaGetPaymentMethodCode(),
            addressSelectBox = _$('.svea_address_selectbox', paymentMethodCode),
            container = addressSelectBox ? addressSelectBox.up('.svea-ssn-container') : null,
            name,
            newLabel,
            label,
            addressBox,
            addressDiv;

        selectedAddress = this.getSelectedAddress();

        if (container) {

            // Update the selected address summary
            addressDiv = $(container).down('.sveaShowAddresses');

            if (addressDiv) {

                // Only update if there is an address selected - otherwise
                // hide the whole address div
                if (selectedAddress.addressSelector !== null) {
                    label = $(container).down('.sveaShowAdressesLabel');
                    newLabel = label.cloneNode(true);
                    $(newLabel).show();
                    if (!selectedAddress.fullName) {
                        name = selectedAddress.firstName + ' ' +
                            selectedAddress.lastName + '<br>';
                    } else {
                        name = selectedAddress.fullName + '<br>';
                    }
                    addressBox = '<address>' + name +
                        (selectedAddress.street || '') + '<br>' +
                        (selectedAddress.zipCode || '') + ' ' +
                        (selectedAddress.locality || '') + '</address>';

                    $(container).down('.sveaShowAddresses').update('')
                        .insert(newLabel)
                        .insert(addressBox);

                    addressDiv.show();
                } else {
                    addressDiv.hide();
                }
            }
        }


        // Set hidden address selector value, it might not be present
        // If it isn't present but svea is not required it's not a big deal but
        // if svea is required and this is not present there is a problem and
        // most likely the order cannot be completed.
        ($$('input[name="payment[svea_info][svea_addressSelector]"]')[0] || {value: null}).value = this.selectedAddressId;

        // Update address field values
        this._setAddressFieldValues();
    },
    /** Set values in address input fields according to the selected address
     *
     * This method does not check if the customer in question is the current
     * customer, it will always set the values.
     *
     * @returns undefined
     */
    _setAddressFieldValues: function() {
        var keyMap = {
            'billing:firstname': 'firstName',
            'billing:lastname': 'lastName',
            'billing:street1': 'street',
            'billing:city': 'locality',
            'billing:postcode': 'zipCode'
        },
            newValues = {},
            selectedAddress = this.getSelectedAddress();

        // New values
        Object.keys(keyMap).each(function(key) {
            newValues[key] = selectedAddress[keyMap[key]];
        });

        // Set values on elements
        _sveaGetReadOnlyElements().each(function(item) {
            item.value = newValues[item.readAttribute('id')];
        });
    },
    /** Make changes in the GUI
     *
     * Does a setup or teardown on the gui. This method is always safe to call.
     *
     * @returns undefined
     */
    setupGui: function() {

        var addressSelectBox = _$('.svea_address_selectbox',
                                  _sveaGetPaymentMethodCode());

        if (addressSelectBox) {
            addressSelectBox.update('');

            // Add all addresses to option element
            if (Object.keys(this.addresses).length > 1) {
                Object.keys(this.addresses).each(function (key) {
                    var item = this.addresses[key],
                        addressString,
                        option;

                    addressString = item.fullName + ', '
                        + item.street + ', '
                        + item.zipCode + ' '
                        + item.locality;

                    option = new Element('option', {
                        value: item.addressSelector
                    }).update(addressString);

                    addressSelectBox.insert(option);
                }.bind(this));

                // Show
                addressSelectBox.show();
            } else {
                // Hide
                addressSelectBox.hide();
            }
        }

        // Update selected address after the address select box is setup
        // This also updates the address field values
        this.updateSelectedAddressGui();

    }

});

/** Store for svea customers */
var _SveaCustomerStore = Class.create({

    customers: null,
    initialize: function() {
        this.customers = {};
    },
    /** Add a customer
     *
     * @param customer _SveaCustomer
     *
     * @return The added _SveaCustomer
     */
    add: function(customer) {

        this.customers[customer.getHash()] = customer;

        return customer;
    },
    /** Add a customer based on a response and selected country, paymentMethod and nationalIdNumber
     *
     * @return The created _SveaCustomer
     */
    addFromResponse: function(response) {
        return this.add(new _SveaCustomer(
            {
                countryCode: _sveaGetBillingCountryCode(),
                customerType: _sveaGetCustomerType(),
                nationalIdNumber: _sveaGetBillingNationalIdNumber()
            },
            response));

    },
    /** Get current customer according to selected billing country, paymentMethod and nationalIdNumber
     *
     * @returns a _SveaCustomer that might be invalid
     */
    getCurrent: function() {
        var invalidCustomer = new _SveaCustomer({
            countryCode: _sveaGetBillingCountryCode(),
            customerType: _sveaGetCustomerType(),
            nationalIdNumber: _sveaGetBillingNationalIdNumber()
        });

        return this.customers[invalidCustomer.getHash()] || invalidCustomer;
    }
});

/** GUI Controller for Svea
 */
var _SveaController = Class.create({

    /** List of payment methods that must use SVEA getAddress if a valid country is selected
     */
    validPaymentMethods: [
        'svea_invoice',
        'svea_paymentplan'
    ],
    /** List of countries that must use SVEA getAddress if a valid payment method is selected
     */
    validCountries: [
        'SE',
        'DK'
    ],
    initialize: function() {
        this.customerStore = new _SveaCustomerStore();
        // Store last state
        this.lastState = this.getCurrentState();
        // If the last state required SVEA
        this.lastStateRequiredSvea = this.sveaAddressIsRequired();

        // Store current address values
        if (!this.sveaAddressIsRequired()) {
            this.lastStateAddressValues = this.getCurrentReadonlyAddressValues();
        } else {
            // If we started as SVEA have a blank address
            this.lastStateAddressValues = {};
        }
    },
    /** Toggle visibility and state of 'ship to different address'-checkbox
     *
     * @param visible If true the checkbox will be visible
     * @param checked If true the checkbox will be checked
     *
     * @returns undefined
     */
    toggleShipToDifferentAddress: function(visible) {
        var $div,
            $elem;

        // call shipping.setSameAsBilling just in case
        /*global shipping */
        if (visible) {
            if (typeof shipping !== 'undefined') {
                shipping.setSameAsBilling(true);
            }
        }

        // Handle streamcheckout checkbox, should not be checked
        $div = $$('.ship-to-different-address');
        if ($div.length === 1) {
            if (!visible) {

                $elem = $($div[0].down('input'));
                if ($elem.checked) {
                    $elem.click();
                }

                $div[0].addClassName('svea-hidden');

            } else {
                $div[0].removeClassName('svea-hidden');
            }
        }

        // Handle onestepcheckout checkbox, should be checked
        $div = $$('.input-different-shipping');
        if ($div.length === 1) {
            if (!visible) {

                $elem = $($div[0].down('input'));
                if (!$elem.checked) {
                    $elem.click();
                }

                $div[0].addClassName('svea-hidden');

            } else {
                $div[0].removeClassName('svea-hidden');
            }
        }

        // Hide actual #shipping_address element because magento doesn't handle
        // it correctly in all cases.
        if (!visible) {
            ($$('#shipping_address')[0] || {hide: function () {}}).hide();
        }

    },
    /** Toggle readonly on readonly elements
     *
     * @param readonly If true the elements will be set to readonly
     *
     * @returns undefined
     */
    toggleReadOnlyElements: function(readonly) {
        _sveaGetReadOnlyElements().each(function(item) {
            if (readonly) {
                item.addClassName('svea-readonly');
                item.writeAttribute('readonly', true);
            } else {
                item.removeClassName('svea-readonly');
                item.writeAttribute('readonly', false);
            }
        });
    },
    /** Check if a svea address is required in the checkout
     *
     * @returns Boolean
     */
    sveaAddressIsRequired: function() {
        var paymentMethod = _sveaGetPaymentMethodCode(),
            countryCode = _sveaGetBillingCountryCode();

        if (paymentMethod === null) {
            return false;
        }
        if (countryCode === null) {
            return false;
        }

        return this.validPaymentMethods.indexOf(paymentMethod) !== -1 && this.validCountries.indexOf(countryCode) !== -1;
    },
    /** Check if svea getAddress may be used
     *
     * This is not the same as it _must_ be used even though it currently returns
     * the same value. When this returns `false` the svea ssn container should be
     * hidden, when true it should be visible.
     *
     * @returns Boolean
     */
    canUseSveaGetAddress: function() {
        return this.sveaAddressIsRequired();
    },
    /** Toggle visibility of ssn container
     *
     * @param visible If true the container will be visible
     *
     * @returns undefined
     */
    toggleSsnContainer: function(visible) {
        var ssnContainer = _sveaGetSsnContainer();
        if (ssnContainer === null) {
            return;
        }
        if (visible) {
            ssnContainer.show();
        } else {
            ssnContainer.hide();
        }
    },
    /** Get current values for all readonly fields
     *
     * @returns Object with current elementId => value
     */
    getCurrentReadonlyAddressValues: function() {
        var rc = {};
        _sveaGetReadOnlyElements().each(function(elem) {
            rc[elem.id] = elem.value;
        });
        return rc;
    },
    /** Check if an objects values are all falsy
     */
    hasOnlyEmptyValues: function(obj) {
        var values = Object.values(obj),
            l = values.length,
            i;

        for (i = 0; i < l; i++) {
            if (values[i]) {
                return false;
            }
        }

        return true;
    },
    /** Setup gui
     *
     * This method should always be called when something that may affect svea
     * is changed.
     *
     * - If we go from non-svea to svea the current readonly values will be stored.
     * - If we go from svea to non-svea the stored readonly values will be restored _unless_ they are missing or all of them are falsy(empty but not string with only spaces in it), in that case the svea values will remain.
     * - If the user went from non-svea to svea with only empty values the svea values will remain
     *
     * Because of everything listed above the following will happen:
     * - User has non-svea selected
     * - User switches to svea with empty values
     * - User fetches correct address from svea
     * - User switches to non-svea, since the original values were all empty the svea address will remain
     * - User svitches back to svea directly
     * - User fetches a _different_ valid address from svea
     * - User switches to non-svea
     * - Now the values from the _first_ address from svea will be set
     *
     * @returns undefined
     */
    setupGui: function() {
        var newState = this.getCurrentState(),
            newStateRequiresSvea = this.sveaAddressIsRequired(),
            newStateAddressValues = this.getCurrentReadonlyAddressValues();

        if (newStateRequiresSvea) {

            // svea-ssn-inputs are required entries
            $$('.svea-ssn-input').invoke('addClassName', 'required-entry');

            this.toggleReadOnlyElements(true);
            this.toggleShipToDifferentAddress(false);

        } else {

            // svea-ssn-inputs are no longer required entries
            $$('.svea-ssn-input').invoke('removeClassName', 'required-entry');

            // Unlock readonly elements
            this.toggleReadOnlyElements(false);
            // Toggle shipToDifferentAddress
            this.toggleShipToDifferentAddress(true);

        }

        if (this.canUseSveaGetAddress()) {
            // Show ssn-container
            this.toggleSsnContainer(true);
        } else {
            // Hide ssn-container
            this.toggleSsnContainer(false);
        }

        // Let the current customer setup gui
        // This clears the readonly fields if this doesn't require svea
        this.customerStore.getCurrent().setupGui();

        // Restore new values and then last values if we went from svea to non-svea
        if (this.lastStateRequiredSvea && !newStateRequiresSvea) {
            var hasOldValues = false;

            if (this.hasOnlyEmptyValues(this.lastStateAddressValues)) {
                // We restore the new values here because if lastStateAddressValues
                // aren't set we want to fill in the current values so they can
                // be used. This is very hackish but time is short.
                $H(newStateAddressValues).each(function(pair){
                    $(pair.key).value = pair.value;
                });
            } else {
                // Restore all values that are saved since they have at least
                // one value
                $H(this.lastStateAddressValues).each(function(pair){
                    $(pair.key).value = pair.value;
                });
            }
        }

        // Restore _current_ address values if we went from non-svea to non-svea
        // because they might have been overwritten by the call to
        // this.customerStore.getCurrent().setupGui()
        if (!this.lastStateRequiredSvea && !newStateRequiresSvea) {
            $H(newStateAddressValues).each(function(pair){
                $(pair.key).value = pair.value;
            });
        }

        // Store lastStateAddressValues unless this is a svea -> svea change
        if (!(newStateRequiresSvea && this.lastStateRequiredSvea)) {
            this.lastStateAddressValues = newStateAddressValues;
        }

        // If OneStepCheckout is used the current payment method
        // and it's additional_data must be saved prior to finalizing
        // the checkout. This is done with the OneStepCheckout method
        // `get_separate_save_methods_function`.

        /*global get_separate_save_methods_function */
        if (typeof get_separate_save_methods_function === 'function') {
            var url = window.sveaOneStepCheckoutSetMethodsSeparateUrl;

            // Note: There is a setting in OneStepCheckout that disables
            // this but even if you turn it of OneStepCheckout will
            // still do these request so we need to do them always.

            // If anything besides paymentMethod changed (onestepcheckout will
            // handle the paymentMethod change.
            if (!(newState.nationalIdNumber === this.lastState.nationalIdNumber &&
                  newState.customerType === this.lastState.customerType &&
                  newState.selectedAddressId === this.lastState.selectedAddressId)) {

                // Don't do this if paymentMethodCode isn't set.
                // Keeping this seperate from above because that expr. is annyoing
                // as-is.
                if (newState.paymentMethodCode) {
                    get_separate_save_methods_function(url, false)();
                }
            }
        }

        // Store last state
        this.lastState = newState;
        // Store if last state required SVEA
        this.lastStateRequiredSvea = newStateRequiresSvea;
    },
    /** Get current state that will determine which address svea should use
     *
     * @returns Object with all key: values that determines customer + address
     */
    getCurrentState: function() {
        return {
            nationalIdNumber: _sveaGetBillingNationalIdNumber(),
            paymentMethodCode: _sveaGetPaymentMethodCode(),
            customerType: _sveaGetCustomerType(),
            selectedAddressId: ($$('input[name="payment[svea_info][svea_addressSelector]"]')[0] || {value: null}).value
        };
    },
    /** Handle a response from svea getAddress()
     *
     * @param data Response data
     *
     * @returns undefined
     */
    handleResponse: function(data) {

        this.customerStore.addFromResponse(data);

        if (data.accepted === false) {
            alert(data.errormessage);
        }

        // Always call setup gui
        this.setupGui();

    },
    /** Setup observers
     *
     * Payment method change events are handled elsewhere, so is customer
     * type change.
     *
     * @returns undefined
     */
    setupObservers: function() {
        var changeCb = this.changeCb.bind(this),
            selectors = [
                'select[name="billing[country_id]"'
            ];

        selectors.each(function(selector) {
            $$(selector).invoke('observe',
                                'change',
                                changeCb);
            });
    },
    /** Callback for when something has changed
     */
    changeCb: function() {
        // Called when something changed
        this.setupGui();
    }
});

/** _SveaController instance
 */
var _sveaController = new _SveaController();

/** Get and update address from svea with an AJAX request
 *
 * @param paymentMethodCode Payment method code, if not set current selected code will be used
 */
function sveaGetAddress(paymentMethodCode)
{
    var ssn = _sveaGetBillingNationalIdNumber(),
        typeElement = _$('input:checked[name*=customerType]', paymentMethodCode),
        countryCode = currentCountry,
        customerType = typeElement ? typeElement.value : 0;

    paymentMethodCode = paymentMethodCode || payment.currentMethod || _sveaGetPaymentMethodCode();

    function startLoading()
    {
        var getAddressButton = _$('.get-address-btn', paymentMethodCode);
        if (getAddressButton) {
            $(getAddressButton).addClassName('loading');
        }
    }

    function stopLoading()
    {
        var getAddressButton = _$('.get-address-btn', paymentMethodCode);
        if (getAddressButton) {
            $(getAddressButton).removeClassName('loading');
        }
    }

    function onSuccess(transport) {
        var json = transport.responseText.evalJSON();

        try {
            _sveaController.handleResponse(transport.responseText.evalJSON());
        } catch (e) {
            // console.warn('_sveaController.handleResponse error', e, transport);
            return;
        }
    }

    startLoading();
    new Ajax.Request(window.getAddressUrl, {
        parameters: {ssn: ssn, type: customerType, cc: countryCode, method: paymentMethodCode},
        onComplete: function (transport) {
            stopLoading();
        },
        onSuccess: onSuccess
    });
}

/** This is called from the template when customer type is changed
 *
 * This needs to be bound to the input in question because the current value is
 * read from $(this).value.
 *
 * @returns undefined
 */
function setCustomerTypeRadioThing()
{
    var customerType = $(this).value;

    // Set hidden input value
    $$('input[name="payment[svea_info][svea_customerType]"]')[0].value = customerType;

    if (currentCountry == 'NL' || currentCountry == 'DE') {
        if (customerType == 1) {
            $$(".forNLDE").invoke('hide');
            $$(".forNLDEcompany").invoke('show');
        } else {
            $$(".forNLDEcompany").invoke('hide');
            $$(".forNLDE").invoke('show');
        }
    } else {
        if (customerType == 1) {
            $$(".label_ssn_customerType_0").invoke('hide');
            $$(".label_ssn_customerType_1").invoke('show');
        } else {
            $$(".label_ssn_customerType_1").invoke('hide');
            $$(".label_ssn_customerType_0").invoke('show');
        }
    }

    // Forward to _sveaController
    _sveaController.setupGui();
}

/** Callback for when an address is selected
 *
 * This function must be bound to the address select element when called.
 */
function sveaAddressSelectChanged()
{
    // Update the selected address id on the current address
    _sveaController.customerStore.getCurrent().setSelectedAddressId($F(this));
}

$(document).observe('dom:loaded', function () {

    /** Patch methods that are used to change payment method
     *
     * The payment selector is fetched with an AJAX request and inserted in the
     * document. In order to react on payment method changes we patch
     * whatever function the method selector might call.
     */
    (function() {
        "use strict";
        /*global Payment Streamcheckout */

        var _oldPaymentSwitchMethod,
            _oldStreamcheckoutSwitchMethod;

        // Patch 'Payment'
        if (typeof Payment !== 'undefined') {
            _oldPaymentSwitchMethod = Payment.prototype.switchMethod;

            Payment.prototype.switchMethod = function(method) {
                _sveaController.changeCb();
                return _oldPaymentSwitchMethod.call(this, method);
            };
        }

        // Patch 'Streamcheckout'
        if (typeof Streamcheckout !== 'undefined') {
            _oldStreamcheckoutSwitchMethod = Streamcheckout.prototype.switchPaymentBlock;

            Streamcheckout.prototype.switchPaymentBlock = function(method) {
                _sveaController.changeCb();
                return _oldStreamcheckoutSwitchMethod.call(this, method);
            };
        }
    })();

    _sveaController.setupObservers();
    _sveaController.setupGui();
});
