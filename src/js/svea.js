var currentSveaAddress;
var customerIdentities;

$("payment_form_customerType_0").observe("change", function (e) {
    customerType = $(this).value;
    if (currentCountry == 'NL' || currentCountry == 'DE') {
        if ($(this).value == 1) {
            $$(".forNLDE").each(function (element) {
                $(element).hide();
            });
            $("forNLDEcompany").show();
        } else {
            $("forNLDEcompany").hide();
            $$(".forNLDE").each(function (element) {
                $(element).show();
            });
        }
    } else {
        if ($(this).value == 1) {
            $("label_ssn_customerType_0").hide();
            $("label_ssn_customerType_1").show();
        } else {
            $("label_ssn_customerType_1").hide();
            $("label_ssn_customerType_0").show();
        }

    }
});

$("payment_form_customerType_1").observe("change", function (e) {
    customerType = $(this).value;
    if (currentCountry == 'NL' || currentCountry == 'DE') {
        if ($(this).value == 1) {
            $$(".forNLDE").each(function (element) {
                $(element).hide();
            });
            $("forNLDEcompany").show();
        } else {
            $("forNLDEcompany").hide();
            $$(".forNLDE").each(function (element) {
                $(element).show();
            });
        }
    } else {
        if ($(this).value == 1) {
            $("label_ssn_customerType_0").hide();
            $("label_ssn_customerType_1").show();
        } else {
            $("label_ssn_customerType_1").hide();
            $("label_ssn_customerType_0").show();
        }

    }
});

function sveaAddressChanged(addressSelector)
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

    if ($('sveaShowAddresses')) {
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

        $('sveaShowAddresses').insert(adress);
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

$('svea_address_selectbox').observe('change', function (e) {
    currentSveaAddress = $F('svea_address_selectbox');
    sveaAddressChanged(currentSveaAddress);
    $('svea_address_selector').value = currentSveaAddress;
});

window.sveaGetAddress = function (e) {
    // Set vars
    var ssn = $("payment_form_ssn").value;
    var type = $$('input:checked[type=radio][name*=payment[svea_info][customerType]]')[0].value;

    $('sveaLoader').show();

    new Ajax.Request(getAddressUrl, {
        parameters: {ssn: ssn, type: type, cc: currentCountry},
        onSuccess: function (transport) {
            var json = transport.responseText.evalJSON();
            $('sveaLoader').hide();
            if (json.accepted == false) {
                if (usingQuickCheckout) {
                    alert(json.errormessage);
                } else {
                    $("sveaShowAddresses").update("<span style='color:red'>" + json.errormessage + "</span>");
                }
                return;
            }

//                var addressElement = $('svea_addressSelector');
//                if (addressElement) {
//                    $(addressElement).remove();
//                }

            // Show dropdown if company, show only text if private customer
            $('svea_address_selectbox').update('');
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

                    $('svea_address_selectbox').insert(option);
                });

                $('svea_address_selectbox').show()
                    .update(selectBox);
            } else {
                $('svea_address_selectbox').hide();
            }

            currentSveaAddress = customerIdentities[0].addressSelector;
            sveaAddressChanged(currentSveaAddress);
            $('svea_address_selector').value = currentSveaAddress;
        }
    });
}