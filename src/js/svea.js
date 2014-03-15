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
        updateFieldsUsingJavascript: false
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

        if (this.config['updateFieldsUsingJavascript']) {
            this.displayCountrySpecificFields();
            $('checkout:form').on('change', '[name*=country_id]',
                this.displayCountrySpecificFields.bindAsEventListener(this));
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

        if (this._requestRunning) {
            return;
        }

        var url = this.config.baseUrl + 'svea_webpay/utility/getaddress';
        var data = {
            'ssn': $F('payment_' + method + '_ssn'),
            'customer_type': $$("input:checked[type=radio][name*='customer_type']")[0].value,
            'country': $F('payment_' + method + '_country'),
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
