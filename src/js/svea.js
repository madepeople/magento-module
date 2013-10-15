/**
 * Contains frontend related functions for the Svea WebPay payment methods. This
 * file depends on the svea_javascript action to be loaded in advance, as it
 * contains store depedent URLs and information used inside this file.
 *
 * Protected by local scope to prevent conflicts with other modules.
 *
 * @author jonathan@madepeople.se
 */
var Svea = Class.create({
    config: {
        baseUrl: null,
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
