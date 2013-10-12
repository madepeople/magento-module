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
            alert('Implement me');
        }

        var url = this.config.baseUrl + 'svea_webpay/utility/getaddress';
        var data = {
            'ssn': $F('payment_' + method + '_ssn')
        };

        new Ajax.Request(url, {
            parameters: data,
            onComplete: function (transport) {
                var obj = transport.responseJSON;
                if (obj.error) {
                    // TODO: Error handling
                    alert(obj.error);
                    return;
                }

                updateBillingAddressForm(obj);
            }
        });
    }
});
