/*global Ajax $ $$ $F currentCountry payment usingQuickCheckout getAddressUrl */

/** Current selected address */
var currentSveaAddress;
/** List of customer addresses from svea */
var customerIdentities;

/** Last get address request that was made
 */
var _sveaLastGetAddressRequest = {
    json: null,
    code: ''
};

/**
 * Should hold the state of the input fields that a user filled in on its own,
 * so we for instance know if "use_for_shipping" was clicked before invoice
 * was selected
 */
var _customerAddressState = {
    'use_for_shipping': null,
    'form': {}
};

function _sveaGetSsnContainer(code) {
    var container;
    if (typeof code !== 'undefined' && code !== '') {
        container = $$('.svea-ssn-container-' + code)[0];
    } else {
        var elements = $$('[class*=svea-ssn-container-]');
        if (elements.length) {
            container = elements[0];
        } else {
            container = $$('.svea-ssn-container')[0];
        }
    }
    return container;
}

/** Get element based on selector and code
 *
 * @param selector What to select under the top element
 * @param code Code that decides which svea container that should be used as top-level, default null
 */
function _$(selector, code)
{
    return _sveaGetSsnContainer(code).down(selector);
}

/** Called when an address was selected */
function sveaAddressChanged(addressSelector, container)
{
    var address;
    for (var i = 0; i < customerIdentities.length; i++) {
        if (customerIdentities[i].addressSelector == addressSelector) {
            address = customerIdentities[i];
            break;
        }
    }

    if (typeof address === 'undefined') {
        return;
    }

    if ($(container).down('.sveaShowAddresses')) {
        if (address.fullName == null) {
            var name = address.firstName + ' ' +
                address.lastName + '<br>';
        } else {
            var name = address.fullName + '<br>';
        }

        var label = $(container).down('.sveaShowAdressesLabel');
        var newLabel = label.cloneNode(true);
        $(newLabel).show();
        var addressBox = '<address>' + name +
            address.street + '<br>' +
            address.zipCode + ' ' +
            address.locality + '</address>';

        $(container).down('.sveaShowAddresses').update('')
            .insert(newLabel)
            .insert(addressBox);
    }

    // Set values for onestep checkouts if billing:firstname is visible
    if ($('billing:firstname').visible()) {
        var sveaMagentoAddressMap = {
            'firstname': 'firstName',
            'lastname': 'lastName',
            'street1': 'street',
            'city': 'locality',
            'postcode': 'zipCode'
        };

        _sveaGetReadOnlyElements().each(function(item) {
            var key = item.readAttribute('id').replace(/.*:/, '');
            item.value = address[sveaMagentoAddressMap[key]];
        });
    }

    // Set selector values, one for the select and one for the hidden
    _$('.svea_address_selectbox', _sveaLastGetAddressRequest.code).value = currentSveaAddress;
    $(container).down('.svea_address_selector').value = currentSveaAddress;
}

/** Handle errors that was returned by getAddress and stored in _sveaLastGetAddressRequest
 */
function _sveaHandleGetAddressErrors() {
    var json = _sveaLastGetAddressRequest.json,
        code = _sveaLastGetAddressRequest.code;

    if (json) {
        if (json.accepted == false) {
            if (usingQuickCheckout) {
                alert(json.errormessage);
            } else {
                _$('.sveaShowAddresses', code).update("<span style='color:red'>" + json.errormessage + "</span>");
            }

            // Clear old address data
            ['firstname', 'lastname', 'street1', 'city', 'postcode']
                .each(function(item) {
                    var id = 'billing:' + item;
                    if ($(id)) {
                        $(id).value = '';
                    }
                });
        }
    }

}

/** Handle addresses that was returned by getAddress and stored in _sveaLastGetAddressRequest
 */
function _sveaHandleGetAddressAddresses()
{
    var code = _sveaLastGetAddressRequest.code,
        json = _sveaLastGetAddressRequest.json;

    if (!json) {
        return;
    }

    var addressesBox = _$('.sveaShowAddresses', code),
        container = _$('.svea_address_selectbox', code).up('.svea-ssn-container');

    // Show dropdown if company, show only text if private customer
    if (addressesBox) {
        addressesBox.update('');
    }

    _$('.svea_address_selectbox', code).update('');
    customerIdentities = json.customerIdentity || [];

    if (customerIdentities.length > 1) {
        customerIdentities.each(function (item) {
            var addressString = item.fullName + ', '
                    + item.street + ', '
                    + item.zipCode + ' '
                    + item.locality;

            var option = new Element('option', {
                'value': item.addressSelector
            }).update(addressString);

            _$('.svea_address_selectbox', code).insert(option);
        });
        _$('.svea_address_selectbox', code).show();
    } else {
        _$('.svea_address_selectbox', code).hide();
    }

    // Store first address as current address if not already set
    if (currentSveaAddress === null) {
        currentSveaAddress = customerIdentities[0].addressSelector;
    }

    // Setup address because it changed. XXX: This will not be called if there was an
    // error because then it returns, which is bad
    sveaAddressChanged(currentSveaAddress, container);

    // Set currentAddress in the address selector
    _$('.svea_address_selector', code).value = currentSveaAddress;
}

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

        readOnlyElements[index] = $id;
    });

    return readOnlyElements;
}

function _sveaSetupGui()
{
    var $shipDiv = $$('.ship-to-different-address');

    if ($('billing:use_for_shipping')) {
        _customerAddressState['use_for_shipping'] = $F('billing:use_for_shipping');
    }
    $$('.svea-ssn-input').invoke('addClassName', 'required-entry');

    $shipDiv = $$('.ship-to-different-address');
    if ($shipDiv.length === 1) {
        var checkbox = $shipDiv[0].down('input');
        if (checkbox.checked) {
            $(checkbox).click();
        }
        $shipDiv[0].addClassName('svea-hidden');
    }
    _sveaGetReadOnlyElements().each(function(item) {
        if (item) {
            _customerAddressState.form[item.readAttribute('id')] = item.value;
            item.addClassName('svea-readonly');
            if (_sveaLastGetAddressRequest.json !== null) {
                item.setValue('');
            }
            item.disable();
        }
    });

    // Show the whole ssn container, always
    _sveaGetSsnContainer(_sveaLastGetAddressRequest.code).show();
}

// Also hides svea container
function _sveaTeardownGui()
{
    var $shipDiv = $$('.ship-to-different-address');

    $$('.svea-ssn-input').invoke('removeClassName', 'required-entry');

    if ($shipDiv.length === 1) {
        $shipDiv[0].removeClassName('svea-hidden');
        if (_customerAddressState['use_for_shipping'] == 0) {
            var checkbox = $shipDiv[0].down('input');
            $(checkbox).click();
        }
    }

    _sveaGetReadOnlyElements().each(function(item) {
        if (item) {
            item.removeClassName('svea-readonly');
            item.enable();

            var value = '';
            if (item.readAttribute('id') in _customerAddressState.form) {
                value = _customerAddressState.form[item.readAttribute('id')];
            }

            // Remove value
            item.setValue(value);
        }
    });

    // Hide the whole ssn container
    _sveaGetSsnContainer(_sveaLastGetAddressRequest.code).hide();
}

function _sveaHandleLastGetAddress()
{
    _sveaSetupGui();
    _sveaHandleGetAddressErrors();
    _sveaHandleGetAddressAddresses();
}

/** Get address from svea
 *
 * @param code I don't know and it's very often null
 */
function sveaGetAddress(code)
{
    var ssn = _$('[name*=[svea_ssn]]', code).value,
        typeElement = _$('input:checked[name*=customerType]', code),
        type = typeElement ? typeElement.value : 0,
        method = code || payment.currentMethod;

    if (!method) {
        method = $$('input:checked[name*=payment[method]]');
        if (method.length) {
            method = method[0].value;
        }
    }

    function startLoading()
    {
        var getAddressButton = _$('.get-address-btn', code);
        if (getAddressButton) {
            $(getAddressButton).addClassName('loading');
        }
    }

    function stopLoading()
    {
        var getAddressButton = _$('.get-address-btn', code);
        if (getAddressButton) {
            $(getAddressButton).removeClassName('loading');
        }
    }

    function onSuccess(transport) {
        var json = transport.responseText.evalJSON();

        // Store last request
        _sveaLastGetAddressRequest = {
            json: json,
            code: code
        };

        // Reset current address
        currentSveaAddress = null;

        // Call setup
        _sveaHandleLastGetAddress();

    }

    startLoading();
    new Ajax.Request(getAddressUrl, {
        parameters: {ssn: ssn, type: type, cc: currentCountry, method: method},
        onComplete: function (transport) {
            stopLoading();
        },
        onSuccess: onSuccess
    });
}

function setCustomerTypeRadioThing()
{
    var customerType = $(this).value;
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
}

/** Callback for when an address is selected
 *
 * Must be bound to the address select element when called
 */
function sveaAddressSelectChanged()
{
    currentSveaAddress = $F(this);
    var container = $(this).up('.svea-ssn-container');
    sveaAddressChanged(currentSveaAddress, container);
}

/** Toggle readonly on address inputs and shipping address when payment method changes.
 *
 * This is buggy because if other modules does the same they might interfere
 * with this but magento has no way of handling unset/set payment method
 *
 * The 'ship to different address' div will have the class 'svea-hidden' if
 * it should be hidden.
 */
function _sveaOnPaymentMethodChange()
{
    var value = $$('input:checked[type="radio"][name="payment[method]"]').pluck("value")[0];

    if (typeof value === 'undefined') {
        // Nothing has been chosen yet, which can be the case with many checkouts
        return;
    }

    if (value.indexOf('svea_') === 0) {
        _sveaHandleLastGetAddress();
    } else {
        _sveaTeardownGui();
    }
}

/** Setup all observers required by svea
 */
function _sveaSetupObservers()
{
    // Address selector
    $$('.svea_address_selectbox').invoke('observe', 'change', sveaAddressSelectChanged);

    // Selector for customer type 0 (person)
    $$('.payment_form_customerType_0').invoke('observe', 'change', setCustomerTypeRadioThing);
    // Selector for customer type 1 (company)
    $$('.payment_form_customerType_1').invoke('observe', 'change', setCustomerTypeRadioThing);

    // On payment method change
    $$('input[name="payment[method]"]').invoke('observe', 'change', _sveaOnPaymentMethodChange);
}

$(document).observe('dom:loaded', function () {
    _sveaSetupObservers();
    _sveaOnPaymentMethodChange();
});
