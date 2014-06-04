var currentSveaAddress;
var customerIdentities;

function _$(selector, code)
{
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
    return $(container).down(selector);
}

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

        var adress = name +
            address.street + '<br>' +
            address.zipCode + ' ' +
            address.locality;

        $(container).down('.sveaShowAddresses').insert(adress);
    }

    // For onestep checkouts, check if fields visible and auto-fill
    if ($("billing:firstname").visible()) {
        $("billing:firstname").value = address.firstName;
        $("billing:lastname").value = address.lastName;
        $("billing:street1").value = address.street;
        $("billing:city").value = address.locality;
        $("billing:postcode").value = address.zipCode;

        ['firstname', 'lastname', 'street1', 'city', 'postcode']
            .each(function(item) {
                var id = 'billing:' + item;
                if ($(id)) {
                    $(id).addClassName('readonly');
                }
            });
    }
}

function sveaGetAddress(code)
{
    function startLoading()
    {
        $('sveaLoader') && $('sveaLoader').show();
    }

    function stopLoading()
    {
        $('sveaLoader') && $('sveaLoader').hide();
    }

    var ssn = _$('[name*=[svea_ssn]]', code).value,
        type = _$('input:checked[type=radio][name*=payment[svea_info][customerType]]', code).value;

    startLoading();

    new Ajax.Request(getAddressUrl, {
        parameters: {ssn: ssn, type: type, cc: currentCountry},
        onSuccess: function (transport) {
            stopLoading();
            var json = transport.responseText.evalJSON();
            if (json.accepted == false) {
                if (usingQuickCheckout) {
                    alert(json.errormessage);
                } else {
                    _$('.sveaShowAddresses', code).update("<span style='color:red'>" + json.errormessage + "</span>");
                }
                return;
            }

            // Show dropdown if company, show only text if private customer
            _$('.sveaShowAddresses', code).update('');
            customerIdentities = json.customerIdentity;
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

                _$('.svea_address_selectbox', code).show()
                    .update(selectBox);
            } else {
                _$('.svea_address_selectbox', code).hide();
            }

            var container = _$('.svea_address_selectbox', code).up('.svea-ssn-container');
            currentSveaAddress = customerIdentities[0].addressSelector;
            sveaAddressChanged(currentSveaAddress, container);
            _$('.svea_address_selector', code).value = currentSveaAddress;
        }
    });
}

$(document).observe('dom:loaded', function () {
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

    $$(".payment_form_customerType_0").invoke('observe', 'change', setCustomerTypeRadioThing);
    $$(".payment_form_customerType_1").invoke('observe', 'change', setCustomerTypeRadioThing);

    $$('.svea_address_selectbox').invoke('observe', 'change', function (e) {
        currentSveaAddress = Form.Element.Serializers.inputSelector(this);
        var container = $(this).up('.svea-ssn-container');
        sveaAddressChanged(currentSveaAddress, container);
        $(container).down('.svea_address_selector').value = currentSveaAddress;
    });
});