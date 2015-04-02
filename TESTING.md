# Testing the frontend

## Test approved/denied customers for all combinations

### Checkouts

* Streamcheckout
* Onepage
* Onestep
* Magento 'classic'

### Countries

* SE
* NO
* DK
* NL
* DE
* FI

### Setup

* "Svea General Setup/Display 'Get Address' button with payment method" with No and Yes
* "Svea General Setup/Display 'Get Address' button regardless of payment method" with No and Yes


### Tests

Test for all countries, all checkouts and "Svea General Setup/Display 'Get Address' button with payment method" Yes/No.

## SE,DK,NO Should have SSN input and getAddress() button

### Checkouts

* Streamcheckout
* Onepage
* Onestep
* Magento 'classic'

### Tests

#### Get Address button should be visible

* Toggle between SE/DK/NO and other countries with svea_payment methods.
* The SSN-input should always be visible for Finland
* The 'Get Address' button should be visible for Finland


## Finland should display ssn input but not getAddress button

### Checkouts

* Streamcheckout
* Onepage
* Onestep
* Magento 'classic'

### Tests

#### SSN input should be visible

* Toggle between Finland and other countries with svea_payment methods.
* The SSN-input should always be visible for Finland
* The 'Get Address' button should not be visible for Finland

## Germany and Netherlands should display birthday inputs

### Checkouts

* Streamcheckout
* Onepage
* Onestep
* Magento 'classic'

### Tests

#### Birthday inputs should be visible

* Toggle between Germany/Netherlands and other countries with svea_payment methods.
* The Birthday inputs should always be visible for Germany/Netherlands
* The 'Get Address' button should not be visible for Germany/Netherlands

## Use Svea getAddress for all payment methods when a valid country is selected

### Checkouts

* Streamcheckout
* Onepage

### Purpose

Some clients wants to use Svea getAddress to fill in address information for other payment methods than the svea payment methods. Therefore the ssn-selector and getAddress() button must be together with the billing address since otherwise it will not be visible.

### Setup

* Svea General Setup/Display 'Get Address' button with payment method = No
* Svea General Setup/Display 'Get Address' button regardless of payment method = Yes

### Tests

#### Container is displayed and works for valid countries

* Valid countries: SE, NO and DK
* Methods: All svea methods and at least one non-svea method

##### Test

1. Select a non-valid country(e.g US)
2. The ssn input and getAddress button _should not_ be visible
3. Select a valid country
4. The ssn input and getAddress() _should_ be visible
5. Test Get Address as usual

#### Container is not displayed for invalid countries

* Invalid countries: USA, NL, DE and FI
* Methods: All svea methods and at least one non-svea method

##### Test

1. Select a valid country
2. The ssn input and getAddress() _should_ be visible
3. Select a non-valid country
4. The ssn input and getAddress button _should not_ be visible
