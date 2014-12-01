/**
 * Contains frontend related functions for the Svea WebPay payment methods. This
 * file depends on the svea_javascript action to be loaded in advance, as it
 * contains store depedent URLs and information used inside this file.
 *
 * Protected by local scope to prevent conflicts with other modules.
 *
 * @author jonathan@madepeople.se
 */
;var Svea = Class.create({
    config: {
        baseUrl: null,
        checkoutType: 'onepage',
        allowSeparateShippingAddress: false,
        automaticallyToggleFields: true
    },
    _requestRunning: false,
    _addressObject: null,

    initialize: function (config)
    {
        config = config || {};

        for (var key in this.config) {
            if (key in config) {
                this.config[key] = config[key];
            }
        }

        // Our initializers both need to set the first state as well as listen
        // for state changes
        var body = $$('body')[0];
        $(body).on('change', '[name*=customer_type]',
            this.initializeFields.bindAsEventListener(this));
        $(body).on('change', '[name*=country_id]',
            this.initializeFields.bindAsEventListener(this));
        $(body).on('click', 'input[name=payment[method]]',
            this.initializeFields.bindAsEventListener(this));

        if ($$('.svea-address-box').length) {
            this.initializeFields();
        }
    },

    /**
     * Callback that is called each time customer_type, country_id or payment_method changes
     *
     */
    initializeFields: function()
    {
        this.toggleIndividualAndCompany();
        this.displayCountrySpecificFields();
        this.fieldConditionsChanged();
        this.toggleMethodNotAvailable();
    },

    /**
     * Toggle between showing the 'method info' div and the 'not available' div
     *
     * This is support for checkouts that doesn't reload payment methods when
     * billing country changes.
     */
    toggleMethodNotAvailable: function() {
        var validCountries = [
            'SE',
            'NO',
            'FI',
            'NL',
            'DE'
        ],
            notAvailableDiv = $('svea-invoice-payment-not-available'),
            infoDiv = $('svea-invoice-payment-information').show();

        if (validCountries.indexOf(this.getBillingCountry()) !== -1) {
            if (infoDiv) {
                infoDiv.show();
            }
            if (notAvailableDiv) {
                notAvailableDiv.hide();
            }
        } else {
            if (infoDiv) {
                infoDiv.hide();
            }
            if (notAvailableDiv) {
                notAvailableDiv.show();
            }
        }
    },

    /**
     * Different checkout modules use different ways to handle the payment
     * selected click as well as have different markup. Instead of making
     * separate cases for every checkout module, we modify markup via input
     * and label and *assume* they are correctly set up in the address
     * template.
     *
     * Since the click listener also differs, we simply use our own listener.
     */
    fieldConditionsChanged: function (event)
    {
        if (!this.config['automaticallyToggleFields']) {
            return;
        }

        /**
         * Disable these form fields that we fetch using getAddress, but only if
         * getAddress is supposed to be used (actually meaning visible in the
         * template). Also, show them if the conditions change back
         *
         * @param lock Boolean, if true the input-fields will be locked to readonly
         */
        function toggleAddressInputFields(lock)
        {
            var allAddressFields = {
                common: [
                    'street',
                    'city',
                    'postcode'
                ],
                individual: [
                    'firstname',
                    'lastname',
                ],
                company: [
                    'company',
                ]
            },
                addressFields,
                customerType,
                cb,
                lockFields = [],
                unlockFields = [];

            if (lock) {
                customerType = this.getCustomerType();
                lockFields = allAddressFields.common.slice(0);

                if (allAddressFields.hasOwnProperty(customerType)) {
                    lockFields = lockFields.concat(allAddressFields[customerType].slice(0));
                    if (customerType === 'individual') {
                        unlockFields = allAddressFields.company.slice(0);
                    } else {
                        unlockFields = allAddressFields.individual.slice(0);
                    }
                }


            } else {
                // Unlock all fields that might have been locked by svea
                unlockFields = allAddressFields.common.slice(0).concat(allAddressFields.individual).concat(allAddressFields.company);

            }

            $(unlockFields).each(function (field) {
                var elements = $$('[name*="billing[' + field + '"]');
                if (!elements.length) {
                    return;
                } else {
                    $(elements).each(function(elem) { elem.writeAttribute('readonly', false); });
                }
            });

            $(lockFields).each(function (field) {
                var elements = $$('[name*="billing[' + field + '"]');
                if (!elements.length) {
                    return;
                } else {
                    $(elements).each(function(elem) { elem.writeAttribute('readonly', true); });
                }
            });

        }

        /** Toggles the possibility use a separate shipping address
         *
         * The elements will get the class 'svea-hidden' if they should be hidden.
         * They will not be made readonly since that may create problems when
         * removing the readonly attribute(in case something else wants to keep
         * it readonly).
         *
         * @param caUse Boolean, if true it will be possible to use a separate shipping address
         */
        function toggleSeparateShippingAddress(canUse) {
            var $elem;

            if (!canUse) {
                // call shipping.setSameAsBilling just in case
                /*global shipping */
                if (typeof shipping !== 'undefined') {
                    shipping.setSameAsBilling(true);
                }
            }

            // Handle onepage billing:use_for_shipping
            $elem = $('billing:use_for_shipping_yes');
            if ($elem !== null) {
                if (!canUse) {
                    if (!$elem.checked) {
                        $elem.click();
                    }
                    $elem.addClassName('svea-hidden');
                } else {
                    $elem.removeClassName('svea-hidden');
                }
            }

            // Handle streamcheckout checkbox, should not be checked
            $elem = $$('.ship-to-different-address');
            if ($elem.length === 1) {
                if (!canUse) {
                    $elem = $($elem[0].down('input'));
                    if ($elem.checked) {
                        $elem.click();
                    }
                    $elem[0].addClassName('svea-hidden');
                } else {
                    $elem[0].removeClassName('svea-hidden');
                }
            }

            // Handle onestepcheckout checkbox, should be checked
            $elem = $$('.input-different-shipping');
            if ($elem.length === 1) {
                if (!canUse) {
                    $elem[0].addClassName('svea-hidden');

                    $elem = $($elem[0].down('input'));
                    if (!$elem.checked) {
                        $elem.click();
                    }

                } else {
                    $elem[0].removeClassName('svea-hidden');
                }
            }

            // Hide actual #shipping_address element because magento doesn't handle
            // it correctly in all cases.
            if (!canUse) {
                ($$('#shipping_address')[0] || { hide: function () {}}).hide();
            }

        }

        // If getAddress is hidden we should show the fields
        var getAddressVisible = false;
        $$('.svea-get-address').each(function (element) {
            if ($(element).visible()) {
                getAddressVisible = true;
            }
        });

        if (this.config.checkoutType !== 'onepage') {
            var method = this.getCurrentMethod();
            if (getAddressVisible && method && method.match(/^svea_(invoice|paymentplan)/)) {
                if (!this.config.allowSeparateShippingAddress) {
                    toggleAddressInputFields.call(this, true);

                    toggleSeparateShippingAddress(false);
                }
            } else {
                toggleAddressInputFields.call(this, false);

                toggleSeparateShippingAddress(true);
            }
        }

    },

    /**
     * Returns the currently selected method, or undefined is not invoice or
     * payment plan
     *
     * @returns string
     */
    getCurrentMethod: function (strip)
    {
        var input = $$('input:checked[name=payment[method]]').length
            ? $$('input:checked[name=payment[method]]')[0] : null;

        if (input) {
            var value = input.value;
            if (value.match(/^svea_/)) {
                if (strip) {
                    return value.replace(/^svea_/, '');
                }
                return value;
            }
        }

        return undefined;
    },

    /**
     * Get selected customer type
     *
     * @return string or null
     */
    getCustomerType: function() {
        var elem = $$('input:checked[name*=customer_type]');
        if (elem.length) {
            return elem[0].value;
        } else {
            return null;
        }
    },

    /**
     * Toggle the blocks specific to private individuals/companies
     *
     * @param event
     */
    toggleIndividualAndCompany: function (event)
    {
        $$('[class*=svea-type]').invoke('hide');

        var elements = $$('input:checked[name*=customer_type]');
        if (elements.length > 0) {
            var type = elements[0].value;
            $$('.svea-type-' + type).invoke('show');
        }
        if (event) {
            this.fieldConditionsChanged();
        }

        var method = this.getCurrentMethod(true);
        if (method) {
            $('svea-' + method + '-address-box').hide();
        }
    },

    /**
     * Get selected billing country
     *
     * @return string country code or null
     */
    getBillingCountry: function() {
        return $$('[name=billing[country_id]]').length ? $$('[name=billing[country_id]]')[0].value : null;
    },

    /**
     * Some countries such as the netherlands have separated the address with
     * the street. So we need to dynamically insert a street + house number
     * field of our own which we concatenate and fill the real fields with. We
     * have to pass this information to the payment method instance.
     *
     * @returns void
     */
    displayCountrySpecificFields: function (event)
    {
        var select;
        if (event) {
            select = event.target;
        } else {
            select = $$('[name=billing[country_id]]').length ? $$('[name=billing[country_id]]')[0] : null;
        }

        if (!select) {
            return;
        }

        var countryId = $(select)[$(select).selectedIndex].value
            .toLowerCase();

        $$('[class*=svea-payment]').invoke('hide');
        $$('.svea-payment-' + countryId).invoke('show');

        if (event) {
            this.fieldConditionsChanged();
        }
    },

    /**
     * getAddress button listener
     *
     * @returns void
     */
    getAddress: function (method)
    {
        var customerType;

        /**
         * Triggers when the address selector dropdown has been changed
         *
         * @param event
         */
        function addressSelectorChanged(event)
        {
            var select = event.target;
            var addressSelector = $F(select);
            updateBillingAddressForm.call(this, addressSelector);
            updateAddressContainer.call(this, addressSelector);
        }

        /**
         * Builds the select box used for choosing between multiple addresses,
         * a common case for companies
         *
         * @param Object obj
         * @return HTMLSelectElement
         */
        function buildAddressSelect(obj)
        {
            var identities = obj.customerIdentity;
            if (identities.length === 1) {
                // Only one address, no select box required
                return;
            }

            var method = this.getCurrentMethod();
            var select = new Element('select', {
                'name': 'payment[' + method + '][' + customerType + '][address_selector]'
            });

            $(identities).each(function (identity) {
                var content = identity.fullName + ", " +
                    identity.street + ", " +
                    identity.zipCode + " " +
                    identity.locality;

                var option = new Element('option', {
                    value: identity.addressSelector
                });

                $(option).update(content.escapeHTML());
                $(select).insert(option);
            });

            $(select).setStyle('width: 100%; margin-bottom: 10px');
            $(select).observe('change', addressSelectorChanged.bindAsEventListener(this));

            return select;
        }

        /**
         * Update the tool tip container with the fetched address and also create
         * and insert a the address select box if needed
         */
        function updateAddressContainer(addressSelector)
        {
            var method = this.getCurrentMethod(true);
            obj = this._addressObject;
            if (obj.customerIdentity.length < 1) {
                $('svea-' + method + '-address-box').hide();
                return;
            }

            var obj = this._addressObject,
                address = getCustomerIdentityFromSelector.call(this, addressSelector);

            if (!address) {
                address = obj.customerIdentity[0];
            }

            $('payment_' + this.getCurrentMethod() + '_address_selector').value = addressSelector;

            // We should in the future insert the address box in different
            // places depending on which checkout module we use, and perhaps
            // also allow developers to customize where the box ends up in a
            // convenient way
            var addressTemplate = new Template($('svea-' + method + '-address-template').innerHTML);
            $('svea-' + method + '-address-box').show();
            $('svea-' + method + '-address-box').down('.svea-loader').hide();
            $('svea-' + method + '-address-box').down('.svea-address-element')
                .update(addressTemplate.evaluate(address));

            $('svea-' + method + '-address-box').down('.svea-address-container')
                .show();
        }

        function getCustomerIdentityFromSelector(selector)
        {
            var obj = this._addressObject, address;
            for (var i = 0; i < obj.customerIdentity.length; i++) {
                if (selector == obj.customerIdentity[i].addressSelector) {
                    address = obj.customerIdentity[i];
                    break;
                }
            }
            return address;
        }

        /**
         * Updates the billing address fields with information fetched from
         * the Svea getAddress call
         *
         * @param int key  Address key
         * @returns void
         */
        function updateBillingAddressForm(addressSelector)
        {
            var obj = this._addressObject,
                address = getCustomerIdentityFromSelector.call(this, addressSelector);

            if (!address) {
                address = obj.customerIdentity[0];
            }

            // Keep track of the fields we've set to prevent for instance the
            // street to be set twice
            var setFields = {};

            for (var key in obj['_identity_parameter_map']) {
                var name = obj['_identity_parameter_map'][key].toLowerCase();
                $$('[name*="billing[' + name + '"]').each(function (element) {
                    if (key in setFields) {
                        return;
                    }
                    element.value = address[key];
                    element.setValue(address[key]);
                    setFields[key] = true;
                });
            }

            updateAddressContainer.call(this, address.addressSelector);
        }

        customerType = $$("input:checked[type=radio][name*='customer_type']")[0].value;

        var ssn_vat = $$('input[name*="[' + customerType + '][ssn_vat]"]')[0].value;
        if (ssn_vat.strip() === '') {
            alert(Translator.translate('Please enter your Social Security Number/VAT Number.').stripTags());
            return;
        }

        if (this._requestRunning) {
            return;
        }

        var url = this.config.baseUrl + 'svea_webpay/utility/getaddress';
        var data = {
            'ssn_vat': ssn_vat,
            'customer_type': customerType,
            'method': method.replace(/svea_/, '')
        };

        this._requestRunning = true;
        new Ajax.Request(url, {
            parameters: data,
            onCreate: function (transport) {
                this._addressObject = null;
                method = method.replace(/^svea_/, '');
                $('svea-' + method + '-address-box').down('.svea-address-element')
                    .update();
                $('svea-' + method + '-address-box').down('.svea-address-container')
                    .hide();
                $('svea-' + method + '-address-box').show();
                $('svea-' + method + '-address-box').down('.svea-loader').show();
            },
            onComplete: (function (transport) {
                this._requestRunning = false;
                method = method.replace(/^svea_/, '');
                var obj = transport.responseJSON;
                if (obj.errormessage && obj.errormessage.length) {
                    // TODO: Error handling
                    $('svea-' + method + '-address-box').hide();
                    alert(obj.errormessage);
                    return;
                }
                this._addressObject = obj;

                updateBillingAddressForm.call(this);

                var addressSelect = buildAddressSelect.call(this, obj);
                $('svea-' + method + '-address-box').down('.svea-select-container')
                    .update(addressSelect);
            }).bind(this)
        });
    }
});
