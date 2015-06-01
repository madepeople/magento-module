/*global window Class $ $$ payment $F $H Ajax Validation */
/** Svea magento module javascript part
 *
 * This module takes care of retrieving addresses from svea and modifying the gui
 * so that inputs are hidden/shown enabled or disabled depending on payment
 * method and billing country.
 *
 * The window._svea object is setup in ssn.phtml.
 *
 * TODO: Lock everything in 'the box' until the getAddress request returns, otherwise one can change customertype during the request >:/
 *
 */

/** SVEA Private customer type denominator for the select element
 */
var _sveaCustomerTypePrivateIntegerValue = 0;

/** SVEA Company customer type denominator for the select element
 */
var _sveaCustomerTypeCompanyIntegerValue = 1;

/** Array of partial element names that whould be readonly for all customer identity types
 *
 */
var _sveaCommonReadOnlyElements = [
    'street1',
    'city',
    'postcode'
];

/** Array of partial element names that whould be readonly for private identity types
 *
 */
var _sveaPrivateReadOnlyElements = [
    'firstname',
    'lastname'
];

/** Array of partial element names that whould be readonly for company identity types
 *
 */
var _sveaCompanyReadOnlyElements = [
    'company'
];

/**
 * SSN validator for svea
 *
 * This validator will only validate the ssn/orgnr if the current method is
 * 'svea_invoice' and the country is Finland and the only validation that
 * currently is done is to check if it's empty or not.
 */
Validation.add(
    'validate-svea-invoice-ssn',
    'This is a required field.',
    function(value) {
        var countryCode = _sveaGetBillingCountryCode(),
            paymentMethodCode = _sveaGetPaymentMethodCode();

        if (paymentMethodCode === 'svea_invoice' && countryCode === 'FI') {
            if (Validation.get('IsEmpty').test(value)) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
});


/** Check if a specific form key should be used
 *
 * If a specific form key should be used it will be stored in
 * `window._svea.formKey`.
 *
 * @returns bool
 */
function _sveaUseFormKey() {
    return window._svea.useFormKey;
}

/** Get the form key that svea uses for it's inputs
 *
 * The formKey is the key used in input names. Example:
 *
 * name=payment[svea_info][svea_customerType]
 *   vs
 * name=payment[svea_paymentplan][svea_customerType]
 *
 * If window._svea.useFormKey is set a single set of inputs are used for all
 * svea payment methods and the formKey should be stored in
 * `window._svea.formKey`.
 * If not the current payment method will be used as form key. This means that
 * the formKey returned here can be non-svea payment method code.
 *
 * @returns string
 */
function _sveaGetFormKey() {
    return _sveaUseFormKey() ? window._svea.formKey : _sveaGetPaymentMethodCode();
}

/* Get nationalIdNumber container
 *
 * @returns Element or null. null should not happen if the dom is correct
 */
function _sveaGetSsnContainer() {
    var elements,
        container,
        formKey = _sveaGetFormKey();

    elements = $$('.svea-ssn-container-' + formKey);
    if (elements.length) {
        return elements[0];
    } else {
        // console.warn("Cannot find ssn container for payment", formKey);
        return null;
    }

}

/** Get element relative a svea ssn container for a specific payment method
 *
 * @param selector What to select under the top element
 *
 * @returns Element or null
 */
function _$(selector)
{
    var ssnContainer = _sveaGetSsnContainer();
    if (ssnContainer) {
        return ssnContainer.down(selector);
    } else {
        // console.warn("Cannot find ssn container sub-element for selector", selector);
        return null;
    }
}

/** Get selected SVEA customer type("0" or "1") for a specific payment method
 *
 * @returns customer type string id ("0" or "1") or null
 */
function _sveaGetCustomerType() {

    var selector = 'input[name="payment[' + _sveaGetFormKey() + '][svea_customerType]"]',
        typeElement = _$(selector);

    if (typeElement !== null) {
        return typeElement.value;
    } else {
        // console.warn('Failed to get svea customer type');
        return null;
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
 * `window._svea` is populated after country is changed so use this to get
 * the current selected billing country code from the billing country select
 * directly.
 *
 * @returns Billing country code or null
 */
function _sveaGetBillingCountryCode() {
    var billingCountryElement = $$('[name="billing[country_id]"]');

    if (billingCountryElement.length) {
        return billingCountryElement[0].value;
    } else {
        billingCountryElement = $('svea-billing-country-id-' + _sveaGetPaymentMethodCode());
        if (billingCountryElement) {
            return billingCountryElement.value;
        } else {
            // console.warn('Could not find billing country id');
            return null;
        }
    }
}

/** Get NationalIdNumber
 *
 * @returns NationalIdNumber or null
 */
function _sveaGetBillingNationalIdNumber() {
    var elem,
        formKey = _sveaGetFormKey();

    elem = _$('[name*=[svea_ssn]]');
    if (elem) {
        return elem.value;
    } else {
        // console.warn("Cannot find svea_ssn");
        return null;
    }
}

/** Get all elements that might have been considered readonly by svea, regardless of customer identity type
 *
 * @returns Array of elements
 */
function _sveaGetAllPossibleReadOnlyElements()
{
    var readOnlyElements = _sveaCommonReadOnlyElements.concat(_sveaPrivateReadOnlyElements).concat(_sveaCompanyReadOnlyElements),
        rc = [];

    readOnlyElements.each(function (item, index) {
        var id = 'billing:' + item,
            $id = $(id);

        if ($id) {
            rc[index] = $id;
        }
    });

    return rc;
}

/** Get array of elements that are considered read-only when svea is used and according to the current customer identity type.
 *
 * These elements should be readonly when svea getaddress is required. However, since the
 * list here changes depending on the customertype it cannot be used to remove the readonly
 * flag. Then a complete list should be used which can be retrieved with
 * `_sveaGetAllPossibleReadOnlyElements`.
 *
 * @returns Array of elements
 */
function _sveaGetCurrentReadOnlyElements()
{
    var customerType = _sveaGetCustomerType(),
        readOnlyElements = _sveaCommonReadOnlyElements,
        rc = [];

    // Company and Private customer identities has separate sets of readonly elements
    // according to SVEA-28.
    if (parseInt(customerType, 10) === _sveaCustomerTypePrivateIntegerValue) {
        readOnlyElements = readOnlyElements.concat(_sveaPrivateReadOnlyElements);
    } else {
        readOnlyElements = readOnlyElements.concat(_sveaCompanyReadOnlyElements);
    }

    readOnlyElements.each(function (item, index) {
        var id = 'billing:' + item,
            $id = $(id);

        if ($id) {
            rc[index] = $id;
        }
    });

    return rc;
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

    initialize: function(config, response, sveaController) {
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
        this.sveaController = sveaController;

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
            addressSelectBox = _$('.svea_address_selectbox'),
            container = addressSelectBox ? addressSelectBox.up('.svea-ssn-container') : null,
            name,
            newLabel,
            label,
            addressBox,
            addressDiv;

        selectedAddress = this.getSelectedAddress();

        if (container) {

            // Select correct address
            if (addressSelectBox.value !== selectedAddress.addressSelector) {
                addressSelectBox.value = selectedAddress.addressSelector;
            }

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
        ($$('input[name="payment[' + _sveaGetFormKey() + '][svea_addressSelector]"]')[0] || {value: null}).value = this.selectedAddressId;

        // Update address field values if getAddress is required.
        if (this.sveaController.getAddressIsRequired()) {
            this._setAddressFieldValues();
        }

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
            'billing:postcode': 'zipCode',
            'billing:company': 'fullName'
        },
            newValues = {},
            selectedAddress = this.getSelectedAddress();

        // New values
        Object.keys(keyMap).each(function(key) {
            newValues[key] = selectedAddress[keyMap[key]];
        });

        // Set values on elements
        // This uses _sveaGetAllPossibleReadOnlyElements because identity type specific
        // elements should be cleared.
        _sveaGetAllPossibleReadOnlyElements().each(function(item) {
            var itemId = item.readAttribute('id'),
                newValue;

            if (newValues.hasOwnProperty(itemId)) {
                newValue = newValues[itemId];
            } else {
                newValue = '';
            }
            item.value = newValue;

        });
    },
    /** Make changes in the GUI
     *
     * Does a setup or teardown on the gui. This method is always safe to call.
     *
     * @returns undefined
     */
    setupGui: function() {

        var addressSelectBox = _$('.svea_address_selectbox');

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
    sveaController: null, // back reference to the svea controller
    initialize: function(sveaController) {
        this.sveaController = sveaController;
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
            response,
            this.sveaController));

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
        }, undefined, this.sveaController);

        return this.customers[invalidCustomer.getHash()] || invalidCustomer;
    }
});

/** GUI Controller for Svea
 */
var _SveaController = Class.create({

    /** List of countries that can use svea
     */
    validCountries: [
        'NL',
        'DE',
        'FI',
        'SE',
        'DK',
        'NO'
    ],
    /** List of countries that _must_ use SVEA getAddress
     */
    getAddressCountries: [
        'SE',
        'DK',
    ],
    /** List of payment methods that must use SVEA getAddress
     */
    getAddressPaymentMethods: [
        'svea_invoice',
        'svea_paymentplan'
    ],
    getAddressUrl: null,
    oneStepCheckoutSetMethodsSeparateUrl: null,
    initialize: function(config) {
        /** Create a new Svea controller
         *
         */

        this.reconfigure(config);

        this.customerStore = new _SveaCustomerStore(this);
        // Store last state
        this.lastState = this.getCurrentState();
        // If the last state required getAddress
        this.lastStateRequiredSvea = this.getAddressIsRequired();

        // Store current address values
        if (!this.getAddressIsRequired()) {
            this.lastStateAddressValues = this.getCurrentReadonlyAddressValues();
        } else {
            // If we started as SVEA have a blank address
            this.lastStateAddressValues = {};
        }

        // Setup gui
        this.setupGui();

        // Setup observers
        this.setupObservers();

    },
    reconfigure: function(config) {
        if (!config.getAddressUrl) {
            throw "config.getAddressUrl not set but required";
        }
        // Url for the getAddress request
        this.getAddressUrl = config.getAddressUrl;

        // The URL OneStepCheckout uses to save payment method and payment
        // method additional data, optional if OneStepCheckout isn't used.
        this.oneStepCheckoutSetMethodsSeparateUrl = config.oneStepCheckoutSetMethodsSeparateUrl || null;

        this.useGetAddressForAllPaymentMethods = config.useGetAddressForAllPaymentMethods;
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

        // Handle onepage billing:use_for_shipping
        $elem = $('billing:use_for_shipping_yes');
        if ($elem !== null) {
            if (!$elem.checked) {
                $elem.click();
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
    /** Toggle readonly elements
     *
     * @param readonly If true the elements will be set to readonly
     *
     * @returns undefined
     */
    toggleReadOnlyElements: function(readonly) {
        var allReadOnlyElements = _sveaGetAllPossibleReadOnlyElements(),
            readOnlyElements = [],
            notReadOnlyElements = [];

        if (readonly) {
            readOnlyElements = _sveaGetCurrentReadOnlyElements();
            // All elements in allReadOnlyElements but not in readOnlyElements should not be
            // read-only
            allReadOnlyElements.each(function(item, key) {
                if (readOnlyElements.indexOf(item) === -1) {
                    notReadOnlyElements.push(item);
                }
            });
        } else {
            notReadOnlyElements = allReadOnlyElements;
        }

        readOnlyElements.each(function(item) {
            item.addClassName('svea-readonly');
            item.writeAttribute('readonly', true);
        });
        notReadOnlyElements.each(function(item) {
            item.removeClassName('svea-readonly');
            item.writeAttribute('readonly', false);
        });
    },
    /** Check if a getAddress request is required
     */
    getAddressIsRequired: function() {
        var paymentMethod = _sveaGetPaymentMethodCode(),
            countryCode = _sveaGetBillingCountryCode();

        if (paymentMethod === null) {
            console.warn('Paymentmethod not found');
            return false;
        }
        if (countryCode === null) {
            console.warn('Countrycode not found');
            return false;
        }

        return this.getAddressPaymentMethods.indexOf(paymentMethod) !== -1 && this.getAddressCountries.indexOf(countryCode) !== -1;
    },
    /** Check if the container with its content should be displayed
     *
     * This is not the same as it _must_ be used even though it currently returns
     * the same value. When this returns `false` the svea ssn container should be
     * hidden, when true it should be visible.
     *
     * @returns boolean
     */
    showContainer: function() {
        var paymentMethod = _sveaGetPaymentMethodCode(),
            countryCode = _sveaGetBillingCountryCode(),
            getAddressIsRequired = this.getAddressIsRequired(),
            alwaysDisplaySsnSelector = window._svea.alwaysDisplaySsnSelector,
            isGetAddressCountry = this.getAddressCountries.indexOf(countryCode) !== -1,
            isSveaPaymentMethod = paymentMethod === null ? false : paymentMethod.indexOf('svea_') === 0,
            isValidCountry = this.validCountries.indexOf(countryCode) !== -1
            ;

        // If getAddress is required the container must be displayed
        if (getAddressIsRequired) {
            return true;
        } else {
            // Never display if countryCode is not set
            if (countryCode === null) {
                return false;
            } else {
                if (isSveaPaymentMethod) {
                    // If svea method _and_ valid country - display
                    return isValidCountry;
                } else {
                    if (alwaysDisplaySsnSelector && isGetAddressCountry) {
                        // Display even if it isn't a svea method
                        // since the ssn selector should always be displayed
                        // and this is a get address country
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
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
    /** Get current values for all current readonly fields
     *
     * @returns Object with current elementId => value
     */
    getCurrentReadonlyAddressValues: function() {
        var rc = {};
        _sveaGetAllPossibleReadOnlyElements().each(function(elem) {
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
            newStateRequiresSvea = this.getAddressIsRequired(),
            newStateAddressValues = this.getCurrentReadonlyAddressValues();

        if (newStateRequiresSvea) {

            // svea-ssn-inputs are required entries
            $$('.svea-ssn-input').invoke('addClassName', 'required-entry');

            this.toggleReadOnlyElements(window._svea.lockRequiredFields);
            this.toggleShipToDifferentAddress(false);

        } else {

            // svea-ssn-inputs are no longer required entries
            $$('.svea-ssn-input').invoke('removeClassName', 'required-entry');

            // Unlock readonly elements
            this.toggleReadOnlyElements(false);
            // Toggle shipToDifferentAddress
            this.toggleShipToDifferentAddress(true);

        }

        if (this.showContainer()) {
            // Show ssn-container
            this.toggleSsnContainer(true);
        } else {
            // Hide ssn-container
            this.toggleSsnContainer(false);
        }

        // Let the current customer setup gui
        // This clears the readonly fields if this doesn't require svea
        this.customerStore.getCurrent().setupGui();

        // If the ssn selector is used for all payment methods we do not
        // try to restore the address if the payment method changed.
        if (!this.useGetAddressForAllPaymentMethods) {
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
        }

        // If OneStepCheckout is used the current payment method
        // and it's additional_data must be saved prior to finalizing
        // the checkout. This is done with the OneStepCheckout method
        // `get_separate_save_methods_function`.

        /*global get_separate_save_methods_function */
        if (typeof get_separate_save_methods_function === 'function') {
            var url = this.oneStepCheckoutSetMethodsSeparateUrl;
            if (!url) {
                throw "config option 'oneStepCheckoutSetMethodsSeparateUrl' not set but required";
            }

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
            selectedAddressId: ($$('input[name="payment[' + _sveaGetFormKey() + '][svea_addressSelector]"]')[0] || {value: null}).value
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

        if ((data.accepted || false) === false) {
            alert(data.errormessage);
        }

        // Always call setup gui
        this.setupGui();

        // Update address manually if getAddress isn't required
        if (!this.getAddressIsRequired()) {
            this.customerStore.getCurrent()._setAddressFieldValues();
        }

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

        // Listen to svea:customerTypeChanged event
        $$('body').invoke('observe',
                          'svea:customerTypeChanged',
                          this.customerTypeChangedCb.bind(this));

        // Listen to svea:getAddress event
        $$('body').invoke('observe',
                          'svea:getAddressFromServer',
                          this.getAddressFromServer.bind(this));

    },
    /** Callback for when something has changed
     */
    changeCb: function() {
        // Called when something changed
        this.setupGui();
    },

    customerTypeChangedCb: function(event) {
        /** Callback for svea:customerTypeChanged event on <body>
         *
         * The event should be fired with a single value which is the new
         * customer type with the value "0" or "1".
         */

        var customerType = event.memo,
        countryCode = _sveaGetBillingCountryCode();

        if (customerType !== "1" && customerType !== "0") {
            throw ["Got invalid customer type", customerType];
        }

        // Set hidden input value
        $$('input[name="payment[' + _sveaGetFormKey() + '][svea_customerType]"]')[0].value = customerType;

        if (countryCode == 'NL' || countryCode == 'DE') {
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

        // Run setupGui
        this.setupGui();

        // Update address values since that will not happen if getAddress
        // isn't required
        if (!this.getAddressIsRequired()) {
            this.customerStore.getCurrent()._setAddressFieldValues();
        }
    },

    /** Get and update address from svea with an AJAX request
     *
     */
    getAddressFromServer: function() {

        var ssn = _sveaGetBillingNationalIdNumber(),
        typeElement = _$('input:checked[name*=customerType]'),
        countryCode = _sveaGetBillingCountryCode(),
        customerType = typeElement ? typeElement.value : 0;

        /**
         * Adds class 'loading' on the getAddressButton
         */
        function startLoading() {
            var getAddressButton = _$('.get-address-btn');
            if (getAddressButton) {
                $(getAddressButton).addClassName('loading');
            }
        }

        /**
         * Removed class 'loading' on the getAddressButton
         */
        function stopLoading() {
            var getAddressButton = _$('.get-address-btn');
            if (getAddressButton) {
                $(getAddressButton).removeClassName('loading');
            }
        }

        /** OnSuccess callback that must be bound to this instance
         */
        function onSuccess(transport) {
            var json = transport.responseText.evalJSON();

            try {
                this.handleResponse(transport.responseText.evalJSON());
            } catch (e) {
                // console.warn('_svea.controller.handleResponse error', e, transport);
                return;
            }
        }

        function on400(transport) {
            var json = transport.responseText.evalJSON();
            if (json.errormessage) {
                alert(json.errormessage);
            }
        }

        startLoading();
        new Ajax.Request(this.getAddressUrl, {
            parameters: {
                ssn: ssn,
                type: customerType,
                cc: countryCode,
                method: _sveaGetPaymentMethodCode()
            },
            onComplete: function (transport) {
                stopLoading();
            },
            onSuccess: onSuccess.bind(this),
            on400: on400.bind(this)
        });
    }

});

/** Get and update address from svea with an AJAX request
 *
 * @deprecated This should be replaced with emitting a 'svea:getAddressFromServer'
 * event on <body> with the new type as argument.
 *
 * Example for a radio with the values 0 or 1:
 *     onclick="(function(){$$('body')[0].fire('svea:customerTypeChanged',$(this).value);}).call(this);"
 *
 */
function sveaGetAddress()
{
    console.warn("This method is deprecated. See comments");
    $$('body')[0].fire('svea:getAddressFromServer');
}

/** This is called from the template when customer type is changed
 *
 * This needs to be bound to the input in question because the current value is
 * read from $(this).value.
 *
 * @deprecated This should be replaced with emitting a 'svea:customerTypeChanged'
 * event on <body> with the new type as argument.
 *
 * Example for a radio with the values 0 or 1:
 *     onclick="(function(){$$('body')[0].fire('svea:customerTypeChanged',$(this).value);}).call(this);"
 *
 * @returns undefined
 */
function setCustomerTypeRadioThing()
{
    console.warn("This method is deprecated, see comments.");
    $$('body')[0].fire('svea:customerTypeChanged', $(this).value);
}

/** Callback for when an address is selected
 *
 * This function must be bound to the address select element when called.
 */
function sveaAddressSelectChanged()
{
    var sveaController = window._svea.controller;
    // Update the selected address id on the current address
    sveaController.customerStore.getCurrent().setSelectedAddressId($F(this));

    // The database needs to find out about the newly selected address
    sveaController.setupGui();

    // Update address values since that will not happen if svea isn't required
    if (!sveaController.getAddressIsRequired()) {
        sveaController.customerStore.getCurrent()._setAddressFieldValues();
    }
}

$(document).observe('dom:loaded', function() {

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
                window._svea.controller.changeCb();
                return _oldPaymentSwitchMethod.call(this, method);
            };
        }

        // Patch 'Streamcheckout'
        if (typeof Streamcheckout !== 'undefined') {
            _oldStreamcheckoutSwitchMethod = Streamcheckout.prototype.switchPaymentBlock;

            Streamcheckout.prototype.switchPaymentBlock = function(method) {
                if ('_svea' in window) {
                    window._svea.controller.changeCb();
                }
                return _oldStreamcheckoutSwitchMethod.call(this, method);
            };
        }
    })();

});
