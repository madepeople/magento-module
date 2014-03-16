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
        allowSeparateShippingAddress: false
    },
    _requestRunning: false,

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
        this.toggleIndividualAndCompany();
        $(body).on('click', '[name*=customer_type]',
            this.toggleIndividualAndCompany.bindAsEventListener(this));

        this.displayCountrySpecificFields();
        $(body).on('change', '[name*=country_id]',
            this.displayCountrySpecificFields.bindAsEventListener(this));

        this.fieldConditionsChanged();
        $(body).on('click', 'input[name=payment[method]]',
            this.fieldConditionsChanged.bindAsEventListener(this));
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
        /**
         * Hide the form fields that we fetch using getAddress, but only if
         * getAddress is supposed to be used (actually meaning visible in the
         * template)
         */
        function hideFields()
        {
            var fields = ['firstname', 'lastname', 'street', 'city', 'zip'];
            alert('hiding fields');
        }

        function showFields()
        {
            alert('showing fields');
        }

        var input;
        if (event) {
            input = event.target;
        } else {
            input = $$('input:checked[name=payment[method]]').length
                ? $$('input:checked[name=payment[method]]')[0] : null;
        }

        // If getAddress is hidden we should show the fields
        var getAddressVisible = false;
        $$('.svea-get-address').each(function (element) {
            if ($(element).visible()) {
                getAddressVisible = true;
            }
        });

        var method = $(input).value;
        if (getAddressVisible && method.match(/^svea_(invoice|paymentplan)/)) {
            hideFields();
        } else {
            showFields();
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
            select = $$('[name=billing[country_id]]').length
                ? $$('[name=billing[country_id]]')[0] : null;
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
        /**
         * Updates the billing address fields with information fetched from
         * the Svea getAddress call
         *
         * @param Object obj
         * @returns void
         */
        function updateBillingAddressForm(obj)
        {
            var billingAddress = obj['_billing_address'], element;
            for (var key in billingAddress) {
                $$('[name=billing[' + key + ']]').each(function (element) {
                    element.value = billingAddress[key];
                    element.setValue(billingAddress[key]);
                });
            }
        }

        var customerType = $$("input:checked[type=radio][name*='customer_type']")[0].value;

        var ssn_vat = $$('input[name*="[' + customerType + '][ssn_vat]"]');
        if (!ssn_vat) {
            alert(Translator.translate('Please enter your Social Security Number/VAT Number.').stripTags());
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
            onComplete: (function (transport) {
                this._requestRunning = false;
                var obj = transport.responseJSON;
                if (obj.errormessage && obj.errormessage.length) {
                    // TODO: Error handling
                    alert(obj.errormessage);
                    return;
                }
                updateBillingAddressForm(obj);
            }).bind(this)
        });
    }
});
