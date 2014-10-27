/*global Class $ $$ payment $F $H Ajax */
/** Svea magento module javascript part
 *
 * This module takes care of retrieving addresses from svea and modifying the gui
 * so that inputs are hidden/shown enabled or disabled depending on payment
 * method and billing country.
 *
 * TODO: Lock everything in 'the box' until the getAddress request returns, otherwise one can change customertype during the request >:/
 *
 */

(function() {
    "use strict";

    // XXX: Don't forget to set debug to false in production
    var debug = true,
        logger = {},
        CustomerId,
        Customer,
        CustomerStore,
        SsnController,
        SveaController,
        companyCustomerTypeId = "1", // Id for company customer types
        privateCustomerTypeId = "0"; // Id for private customer types

    function noop() {
        /** No-op function */
    }

    function getBillingCountrySelect() {
        /** Get <select> element for billing country */
        return $$('[name="billing[country_id]"]')[0];
    }

    function getPaymentMethod() {
        /** Get selected payment method
         *
         * @returns string or null
         */
        var elem = $$('input:checked[name*=payment[method]]');

        if (elem.length) {
            return elem[0].value;
        } else {
            logger.warn("Cannot find payment method");
            return null;
        }
    }

    // TODO: Not very safe since console might not have all these methods
    if (debug) {
        logger = console;
    } else {
        logger = {
            debug: noop,
            log: noop,
            err: noop,
            warn: noop,
            trace: noop
        };
    }

    /** Representation of a customer id
     *
     * A customer id consists of three parts:
     *
     * - nationalIdNumber
     * - customerType
     * - countryCode
     */
    CustomerId = Class.create({
        /** Hash string */
        nationalIdNumber: null,
        customerType: null,
        countryCode: null,
        /** String hash for comparing CustomerIds */
        hash: null,
        initialize: function(id) {
            id = id || {};
            // nationalIdNumber can be an empty string or null
            this.nationalIdNumber = id.hasOwnProperty('nationalIdNumber') ? id.nationalIdNumber : null;
            this.customerType = id.customerType || null;
            this.countryCode = id.countryCode || null;

            // All null is allowed as id
            if (this.nationalIdNumber === null &&
                  this.customerType === null &&
                  this.countryCode === null) {
                // Valid id - all null
            } else {
                // Invalid if any part is null
                if (this.nationalIdNumber === null ||
                    this.customerType === null ||
                    this.countryCode === null) {
                    throw ["Invalid CustomerId", id];
                }
            }

            this.hash = [
                this.nationalIdNumber,
                this.customerType,
                this.countryCode
            ].join('/');
        }
    });

    Customer = Class.create({
        valid: false,
        /** Map of addressSelector: address object
         */
        addresses: null,
        /** CustomerId */
        id: null,
        initialize: function(id, response) {
            /** Create a new Svea customer returned from svea getAddress
             *
             * @param id An object with id parts in it, will be used to create CustomerId
             * @param response Response object from getAddress
             */
            this.id = new CustomerId(id);
            this.response = response || {accepted: false};
            this.valid = this.response.accepted && this.response.customerIdentity.length > 0;
        },
        getAddress: function(addressSelector) {
            /** Get address
             *
             * @param addressSelector The address id
             *
             * @return address object or null
             */
            var i,
                l = this.response.customerIdentity.length;
            for (i = 0; i < l; i++) {
                if (this.response.customerIdentity[i].addressSelector === addressSelector) {
                    return this.response.customerIdentity[i];
                }
            }

            return null;
        }
    });

    /** A store of Customers
     */
    CustomerStore = Class.create({
        customers: null,
        initialize: function() {
            this.customers = {};
        },
        add: function(customer) {
            /** Add a customer
             *
             * @param customer Customer
             *
             * @return The added Customer
             */
            this.customers[customer.id.hash] = customer;
            return customer;
        },
        has: function(customerId) {
            /** Check if a customer exists in this store
             *
             * @return boolean
             */
            return this.customers.hasOwnProperty(customerId.hash);
        },
        get: function(customerId) {
            /** Get customer
             *
             * @throws If Customer with customerId isn't set
             *
             * @return Customer
             */
            if (!this.has(customerId)) {
                throw ["No such customer", customerId];
            } else {
                return this.customers[customerId.hash] || null;
            }
        },
        addFromResponse: function(responseData, customerId) {
            /** Add a customer based on a response and options
             *
             * @param responseData The response object, even if its invalid
             * @param customerId CustomerId
             *
             * @return The new Customer
             */
            return this.add(new Customer(customerId, responseData));
        }
    });

    SsnController = Class.create({
        id: null,
        /** Configuration
         *
         * - id
         * - lockFields
         */
        config: null,
        element: null,
        initialize: function(config) {
            this.id = config.id;
            this.config = {
                lockFields: true
            };
            this.config = Object.extend(this.config, config);
            this.element = $$('.svea-ssn-container-' + this.id)[0];
            this.setupObservers();
        },
        getAddress: function() {
            /** Get address from server via SveaController
             *
             * This method notifies the SveaController that it should fetch an
             * address from the server.
             */
            var customerId = {
                controllerId: this.id,
                customerType: this.getCustomerTypeId(),
                nationalIdNumber: this.getSsn()
            };

            console.log('Notify getAddress');
            SveaController.notifyInstances('getAddress', customerId);
        },
        setupObservers: function() {
            var ctChangeCb = (function(event) {
                this.customerTypeChangeCb(event.target.value);
                }).bind(this),
                getAddressClickCb = (function(event) {
                    this.getAddress();
                }).bind(this),
                ssnChangeCb = (function(event) {
                    this.ssnChangeCb(event.target.value);
                }).bind(this),
                addressSelectorChangeCb = (function(event) {
                    this.addressSelectorChangeCb(event.target.value);
                }).bind(this);

            this.element.select('.payment_form_customerType_0').invoke(
                'observe',
                'change',
                ctChangeCb);

            this.element.select('.payment_form_customerType_1').invoke(
                'observe',
                'change',
                ctChangeCb);

            this.element.select('button.get-address').invoke(
                'observe',
                'click',
                getAddressClickCb);

            // Listen on keyup for ssn changes
            this.element.select('input.svea-ssn-input').invoke(
                'observe',
                'keyup',
                ssnChangeCb);

            // Listen on address selector changes
            this.getAddressSelectorSelect().observe(
                'change',
                addressSelectorChangeCb);
        },
        getHiddenCustomerTypeInput: function() {
            /** Get hidden input where customer type is stored
             */
            return this.element.select('input[name="payment[' + this.id + '][svea_customerType]"]')[0];
        },
        getHiddenAddressSelectorInput: function() {
            /** Get hidden input where address selector is stored
             */
            return this.element.select('input[name="payment[' + this.id + '][svea_addressSelector]"]')[0];
        },
        getCustomerTypeId: function() {
            /** Get customer type id from hidden input
             */
            return this.getHiddenCustomerTypeInput().value;
        },
        getSsnInput: function() {
            /** Get the ssn input
             */
            return this.element.select('input.svea-ssn-input')[0];
        },
        getSsn: function() {
            /** Get ssn from ssn input
             */
            return this.getSsnInput().value;
        },
        setSsn: function(value) {
            /** Set ssn value
             *
             * This only sets the ssn value, nothing else happens
             */
            if (this.getSsn() !== value) {
                console.log("setting ssn value", 'new', value, 'old', this.getSsn());
                this.getSsnInput().setValue(value);
            }
        },
        getAddressSelector: function() {
            /** Get addressSelector from select
             */
            return this.getAddressSelectorSelect().value;
        },
        setAddressSelector: function(value) {
            /** Set address selector value
             *
             * This only sets the address selector value and hidden, nothing else
             */
            this.getAddressSelectorSelect().setValue(value);
            this.getHiddenAddressSelectorInput().value = value;
        },
        getCountryCode: function() {
            /** Get country code from billing address
             */
            return getBillingCountrySelect().value;
        },

        customerTypeChangeCb: function(id) {
            /** Called when the customer type was changed by this controller
             */
            // Update hidden value
            this.getHiddenCustomerTypeInput().value = id;
            this.notifyValuesChanged();
        },
        setCustomerTypeId: function(value) {
            /** Set customer type value
             *
             * This only sets the value and hidden, nothing else
             */
            if (value === '0') {
                this.element.select('.payment_form_customerType_0')[0].writeAttribute('checked', true);
                this.element.select('.payment_form_customerType_1')[0].writeAttribute('checked', false);
            } else {
                this.element.select('.payment_form_customerType_0')[0].writeAttribute('checked', false);
                this.element.select('.payment_form_customerType_1')[0].writeAttribute('checked', true);
            }
            this.getHiddenCustomerTypeInput().setValue(value);
        },
        addressSelectorChangeCb: function(addressSelector) {
            /** Called when the address selector was changes by this controller
             */
            // Update hidden value
            this.getHiddenAddressSelectorInput().value = addressSelector;
            this.notifyValuesChanged();
        },
        oldSsn: null,
        ssnChangeCb: function(ssn) {
            /** Callback for when the ssn was changed by this controller
             */
            if (ssn !== this.oldSsn) {
                this.oldSsn = ssn;
                this.notifyValuesChanged();
            }
        },
        getSelectorValues: function() {
            /** Get current selector values */
            return {
                nationalIdNumber: this.getSsn(),
                customerType: this.getCustomerTypeId(),
                countryCode: this.getCountryCode(),
                addressSelector: this.getAddressSelector() || null
            };
        },
        /** Flag for if SveaController should be notified about value changes
         */
        notifyValueChanges: true,
        newSelectorValues: function(values) {
            /** New selector values
             *
             * This method is called by SveaController to set new selector values.
             */
            var currentValues = this.getSelectorValues();

            // Don't notify SveaController
            this.notifyValueChanges = false;

            this.setSsn(values.nationalIdNumber);
            this.setCustomerTypeId(values.customerType);
            this.setAddressSelector(values.addressSelector);

            this.notifyValueChanges = true;
        },
        notifyValuesChanged: function() {
            /** Notify SveaController that the values has changed */

            // If this flag is false it means we are setting values that we got
            // from the svea controller
            if (this.notifyValueChanges) {
                SveaController.notifyInstances('newSelectorValues',
                                               this.getSelectorValues());
            }
        },

        getAddressButtonWrapper: function() {
            /** Get wrapper element for the Get Address button
             */
            return this.element.select('.get-address-btn')[0];
        },
        /** Current customer address object
         *
         * Keys:
         *
         * - customerId: CustomerId or null
         * - addressSelector: string or null
         *
         */
        currentCustomerAddress: null,
        setCurrentCustomerAddress: function(customer, addressSelector) {
            /** Set selected customer address
             *
             * - Updates ssn selector
             * - Updates customerType
             * - Adds address selector for the customer if the customer has more than one address.
             *
             * @param customer Customer or null
             * @param addressSelector Address selector or null, if customer isn't null this cannot be null
             */

            function updateGui(customer, addressSelector) {
                var addresses = null,
                    addressSelectBox = this.getAddressSelectorSelect();

                // Reset addressSelectBox
                addressSelectBox.update('');
                if (customer !== null) {
                    console.log('new customer', customer);

                    // Update address selector
                    if (customer.valid) {
                        addresses = customer.response.customerIdentity;

                        if (addresses.length > 1) {
                            addresses.each(function (address) {
                                var addressString,
                                    option;

                                addressString = address.fullName + ', '
                                    + address.street + ', '
                                    + address.zipCode + ' '
                                    + address.locality;

                                option = new Element('option', {
                                    value: address.addressSelector
                                }).update(addressString);

                                addressSelectBox.insert(option);
                            }.bind(this));

                            // Show the box
                            addressSelectBox.show();

                        } else {

                            // Hide the box
                            addressSelectBox.hide();
                        }
                    }

                    // Set new values
                    this.setSsn(customer.id.nationalIdNumber);
                    this.setCustomerTypeId(customer.id.customerType);
                    this.setAddressSelector(addressSelector);

                    this.currentCustomerAddress = {
                        customerId: customer.id,
                        addressSelector: addressSelector
                    };

                } else {

                    // Set values
                    this.setSsn("");
                    this.setCustomerTypeId("0"); // Default value for customer type
                    this.setAddressSelector(""); // No value

                    addressSelectBox.hide();

                    this.currentCustomerAddress = {
                        customerId: new CustomerId(),
                        addressSelector: null
                    };
                }

                this.updateSelectedAddressSummary(customer, addressSelector);
            }

            // Prevent notifying SveaController
            this.notifyValueChanges = false;
            updateGui.bind(this)(customer, addressSelector);
            this.notifyValueChanges = true;

        },
        getAddressSelectorSelect: function() {
            return this.element.select('select.svea_address_selectbox')[0];
        },
        getAddressSummaryWrapperElement: function() {
            return this.element.select('div.svea-address-summary')[0];
        },
        updateSelectedAddressSummary: function(customer, addressSelector) {
            /** Update the current selected address summary */
            var summaryWrapper = this.getAddressSummaryWrapperElement(),
                addressElement = summaryWrapper.select('address')[0],
                address = null;

            function getSummaryHtml(address) {
                // Get summary node as HTML
                var name;
                if (!address.fullName) {
                    name = address.firstName + ' ' +
                        address.lastName + '<br>';
                } else {
                    name = address.fullName + '<br>';
                }

                return name +
                    (address.street || '') + '<br>' +
                    (address.zipCode || '') + ' ' +
                    (address.locality || '');
            }

            if (customer === null) {
                // Hide if customer is null
                summaryWrapper.hide();
            } else {
                // Update address summary and then show
                address = customer.getAddress(addressSelector);
                addressElement.update('').insert(getSummaryHtml(address));
                summaryWrapper.show();
            }
        }
    });

    /**
     * This controller handles the details which are not SsnController specific
     *
     * What this controller does:
     *
     * - Patches different checkout methods
     * - Updates billing address when a new address is selected
     * - Sets current selected customer on ssncontroller when they become active
     */
    SveaController = Class.create({
        config: null,
        /** Map of ssn controllers
         *
         * This is required because if the ssn controllers are initialized
         * in payment method markup that is fetched by ajax they might redefine
         * existing controllers.
         */
        ssnControllers: null,
        /** CustomerStore
         */
        customerStore: null,
        initialize: function(config) {
            /** Create a new SveaController
             *
             * @param config Configuration options, can be added later by calling reconfigure()
             */
            this.config = {};
            if (config) {
                this.reconfigure(config);
            }
            this.customerStore = new CustomerStore();
            this.ssnControllers = {};
            this.setupObservers();
        },
        reconfigure: function(config) {
            /** Reconfigure this instance
             *
             * This takes care of patching different checkouts, which is an ugly
             * story.
             */
            this.config = config;
            this.patchCheckouts();
        },
        setupObservers: function() {
            this.observe('reconfigure');
            this.observe('getAddress');
            this.observe('setCurrentCustomerAddress');
            this.observe('newSelectorValues');
            this.observe('addSsnControllerById');
            this.observe('paymentMethodChanged');
        },
        addSsnControllerById: function(config) {
            var controller;

            if (this.ssnControllers.hasOwnProperty(config.id)) {
                delete this.ssnControllers[config.id];
            }

            controller = new SsnController(config);
            this.ssnControllers[controller.id] = controller;
        },
        newSelectorValues: function(values) {
            /** Notifies that there are a new selector values
             *
             * Object:
             * - countryCode
             * - nationalIdNumber
             * - customerType
             * - addressSelector
             *
             * @param values Object with new values
             */
            var customerId,
                customer,
                address;

            // TODO: There is no listener for the billing country selector yet
            console.log('got new values', values);

            // Check if there is a valid customer for these values
            // We don't care about the addressSelector because it will reset to
            // the first valid address if it is invalid
            if (values.countryCode &&
                values.nationalIdNumber &&
                (values.customerType == '0' || values.customerType == '1')) {
                // Potential CustomerId
                customerId = new CustomerId(values);
                if (this.customerStore.has(customerId)) {
                    customer = this.customerStore.get(customerId);
                    this.setCurrentCustomerAddress(customer.id, values.addressSelector);
                    return;
                }
            }

            // Reset current customer
            this.setCurrentCustomerAddress(null, null);

            // Notify the controllers about the new values
            $H(this.ssnControllers).each(function(pair) {
                pair.value.newSelectorValues(values);
            });

        },
        /** Last selected address
         *
         * Object with:
         * - customerId
         * - addressSelector
         *
         * TODO: Will we have to notify the server when changing to a
         * payment method that previously had another selected address?
         */
        currentCustomerAddress: null,
        setCurrentCustomerAddress: function(customerId, addressSelector) {
            /** Set selected customer address
             *
             * This will populate the billing address if the address is valid.
             *
             * @param customerId CustomerId or null
             * @param addressSelector Selected address or null
             */
            var customer = null;

            if (customerId === null) {
                // Unset current address
                this.currentCustomerAddress = null;
                // If this is one of our payment methods we clear the billing
                // address.
                if (this.ssnControllers.hasOwnProperty(getPaymentMethod())) {
                    this.setBillingAddressValues({});
                } else {
                    console.log('No controller for this method');
                }

            } else {
                if (this.customerStore.has(customerId)) {
                    // Found the customer - set it to current customer
                    customer = this.customerStore.get(customerId);
                    if (customer.valid) {
                        addressSelector = addressSelector || customer.response.customerIdentity[0].addressSelector;
                        // Set addressSelector to first address if it doesn't exist
                        // This is required because the SsnControllers will
                        // not have a valid address selector when they toggle
                        // customer.
                        if (customer.getAddress(addressSelector) === null) {
                            addressSelector = customer.response.customerIdentity[0].addressSelector;
                        }
                        this.currentCustomerAddress = {
                            customerId: customerId,
                            addressSelector: addressSelector
                        };

                        this.displayCurrentCustomerAddress();
                    } else {
                        // Invalid customer - set to null
                        this.currentCustomerAddress = null;
                        addressSelector = null;
                    }
                } else {
                    this.currentCustomerAddress = null;
                    addressSelector = null;
                    if (this.customerStore.has(getPaymentMethod())) {
                        this.setBillingAddressValues({});
                    }
                }
            }

            // Notify all ssn-controllers manually
            $H(this.ssnControllers).each(function(pair) {
                pair.value.setCurrentCustomerAddress(customer, addressSelector);
            });

        },
        displayCurrentCustomerAddress: function() {
            /** Displays current selected customer address as billing address
             *
             * Populates the billing address if there is a valid selected
             * customer address.
             *
             * If there isn't a valid customer address and we have a controller
             * for the current payment method we reset the billing address.
             */
            if (this.currentCustomerAddress !== null) {
                if (this.customerStore.has(this.currentCustomerAddress.customerId)) {
                    this.setBillingAddressValues(this.customerStore.get(this.currentCustomerAddress.customerId).getAddress(this.currentCustomerAddress.addressSelector));
                }
            } else {
                if (this.ssnControllers.hasOwnProperty(getPaymentMethod())) {
                    this.setBillingAddressValues({});
                }
            }
        },
        setBillingAddressValues: function(address) {
            /** Populate the billing address with address values
             *
             * @param address Address returned by SVEA
             */
            var keyMap = {
                'billing:firstname': 'firstName',
                'billing:lastname': 'lastName',
                'billing:street1': 'street',
                'billing:city': 'locality',
                'billing:postcode': 'zipCode',
                'billing:company': 'fullName'
            },
            newValues = {};

            // New values
            Object.keys(keyMap).each(function(key) {
                if (address.hasOwnProperty(keyMap[key])) {
                    newValues[key] = address[keyMap[key]];
                } else {
                    newValues[key] = "";
                }
            });

            // Set values on elements
            // This uses _sveaGetAllPossibleReadOnlyElements because identity
            // type specific elements should be cleared.
            this.getUnlockElements().each(function(item) {
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
        getAddress: function(options) {
            /** Perform a getAddress request
             *
             * @param options: Options object with:
             *
             * - controllerId: SsnController id
             * - customerType: Customer type
             * - nationalIdNumber: ssn
             */
            var countryCode = this.getBillingCountryCode(),
                ssnController = this.ssnControllers[options.controllerId],
                addressButtonWrapper = ssnController.getAddressButtonWrapper(),
                // Convert frontend names to backend names
                requestParameters = {
                    ssn: options.nationalIdNumber,
                    type: options.customerType,
                    cc: countryCode,
                    method: ssnController.id
                };

            /**
             * Adds class 'loading' on the getAddressButtonWrapper
             */
            function startLoading() {
                addressButtonWrapper.addClassName('loading');
            }

            /**
             * Removed class 'loading' on the getAddressButtonWrapper
             */
            function stopLoading() {
                addressButtonWrapper.removeClassName('loading');
            }

            /** OnSuccess callback that must be bound to this instance
             */
            function onSuccess(transport) {
                var json = transport.responseText.evalJSON();

                try {
                    this.handleGetAddressResponse(transport.responseText.evalJSON(), requestParameters);
                } catch (e) {
                    logger.warn('SveaController.getAddress.onSuccess', e, transport);
                    return;
                }
            }

            startLoading();
            new Ajax.Request(this.config.getAddressUrl, {
                parameters: requestParameters,
                onComplete: function (transport) {
                    stopLoading();
                },
                onSuccess: onSuccess.bind(this)
            });
        },
        handleGetAddressResponse: function(result, requestParameters) {
            /** Handle a getAddress response
             *
             * This method is separated from extracting response object and
             * parameters from a transport because it allows for a way to add
             *
             * @param result Object returned by the request
             * @param requestParameters Request paramters
             */
            // Convert backend names to frontend names
            var customerId = new CustomerId({
                nationalIdNumber: requestParameters.ssn,
                countryCode: requestParameters.cc,
                customerType: requestParameters.type
            }),
                customer = this.customerStore.addFromResponse(result, customerId);

            // Alert on error
            if (result.accepted === false) {
                alert(result.errormessage);
            }
            if (customer.valid) {
                this.setCurrentCustomerAddress(customer.id, null);
            } else {
                // TODO: Should we revert to former valid address here?
                this.setCurrentCustomerAddress(null, null);
            }
        },
        /** Array of partial element names that whould be readonly for all customer identity types
         *
         * Class variable - should never be modified
         */
        commonLockedElements: [
            'street1',
            'city',
            'postcode'
        ],
        /** Array of partial element names that whould be readonly for private identity types
         *
         * Class variable - should never be modified
         */
        privateLockedElements: [
            'firstname',
            'lastname'
        ],

        /** Array of partial element names that whould be readonly for company identity types
         *
         * Class variable - should never be modified
         */
        companyLockedElements: [
            'company'
        ],
        getUnlockElements: function() {
            /** Get list elements that should be unlocked */
            var elements = this.commonLockedElements.concat(this.privateLockedElements).concat(this.companyLockedElements),
                rc = [];
            elements.each(function (item, index) {
                var id = 'billing:' + item,
                    $id = $(id);
                if ($id) {
                    rc[index] = $id;
                }
            });

            return rc;
        },
        getLockElements: function(customerType) {
            /** Get list of elements that should be locked
             *
             * @param customerType "0" or "1"
             */
            var elements = this.commonLockedElements,
                rc = [];

            // Company and Private customer identities has separate sets of
            // readonly elements according to SVEA-28.
            if (parseInt(customerType, 10) === privateCustomerTypeId) {
                elements = elements.concat(this.privateLockedElements);
            } else {
                elements = elements.concat(this.companyLockedElements);
            }

            elements.each(function (item, index) {
                var id = 'billing:' + item,
                    $id = $(id);

                if ($id) {
                    rc[index] = $id;
                }
            });

            return rc;
        },
        unlockElements: function() {
            this.getUnlockElements().each(function(item) {
                item.removeClassName('svea-readonly');
                item.writeAttribute('readonly', false);
            });
        },
        lockElements: function(customerTypeId) {
            this.getLockElements(customerTypeId).each(function(item) {
                item.addClassName('svea-readonly');
                item.writeAttribute('readonly', true);
            });
        },
        paymentMethodChanged: function(paymentMethod) {
            /** Handle payment method changes
             *
             * If there is a controller with id=paymentMethod we lock elements
             *
             * @param paymentMethod The new payment method
             */
            var controller = null,
                customerAddress = null;

            logger.debug("Payment method change", paymentMethod);

            controller = this.ssnControllers[paymentMethod] || this.ssnControllers['svea_info'] || null;

            if (controller !== null) {
                // Found controller

                // First lock down inputs if we should
                if (controller.config.lockFields) {
                    this.lockElements(controller.getCustomerTypeId());
                } else {
                    this.unlockElements();
                }

                // Display current customer address
                this.displayCurrentCustomerAddress();

            } else {
                // Unlock elements just to be safe
                this.unlockElements();
            }
        },
        getBillingCountryCode: function() {
            /** Get country code for billing address
             */
            return getBillingCountrySelect().value;
        },
        patchedCheckouts: null,
        patchCheckouts: function() {
            /** Patch various checkout methods
             *
             * It's safe to call this function any number of times, it
             * will keep track of which checkout methods that are patched.
             *
             * This will _not_ work if svea.js is loaded before the checkouts js.
             */
            if (this.patchedCheckouts === null) {
                this.patchedCheckouts = [];
            }
            logger.debug('Patching checkouts');

            // Patch 'Streamcheckout'
            /*global Streamcheckout */
            if (this.patchedCheckouts.indexOf('Streamcheckout') === -1 && typeof Streamcheckout !== 'undefined') {
                (function() {
                    var old = Streamcheckout.prototype.switchPaymentBlock;
                    logger.debug('Patching Streamcheckout');
                    Streamcheckout.prototype.switchPaymentBlock = function(method) {
                        SveaController.notifyInstances('paymentMethodChanged', method);
                        return old.call(this, method);
                    };
                })();
                this.patchedCheckouts.push('Streamcheckout');
            }

            // Patch 'Payment'
            /*global Payment */
            if (this.patchedCheckouts.indexOf('Payment') === -1 && (typeof Payment !== 'undefined')) {
                (function() {
                    var old = Payment.prototype.switchMethod;
                    logger.debug('Patching Payment');
                    Payment.prototype.switchMethod = function(method) {
                        SveaController.notifyInstances('paymentMethodChanged', method);
                        return old.call(this, method);
                    };
                })();
                this.patchedCheckouts.push('Payment');
            }
        },
        observe: function(eventName) {
            /** Helper function for observing events on 'body'
             */
            $$('body').invoke('on', 'svea:' + eventName,
                              (function(event) {
                                  this[eventName](event.memo);
                              }).bind(this)
                             );
        }
    });

    // Add class methods
    Object.extend(SveaController, {
        notifyInstances: function(eventName, memo) {
            /** Notify all instances
             *
             * @param eventName Name of the event
             * @param memo The data that the event will be called with
             */
            $$('body').invoke('fire', 'svea:' + eventName, memo);
        }
    });

    // Expose SveaController
    /*global window */
    window.SveaController = SveaController;

    $(document).observe('dom:loaded', function() {
        // Create a new svea controller
        new SveaController();
    });

})();
