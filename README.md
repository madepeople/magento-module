# Magento - Svea WebPay payment module
## Version 4.0
This module is updated for the latest payment systems at SveaWebPay.
The module is tested in Magento 1.7, but will work with older versions to
Supported countries are
**Sweden**, **Norway**, **Finland**, **Denmark**, **The Netherlands**, **Germany**.

## Before installation
### **If you are upgrading from a previous version of this module, please contact Svea before installing to set your account settings correct.**

1. You will not be able to Invoice the old orders after the upgrade, so make sure they are already invoiced. Alternatively invoice them from Svea admin and invoice offline in Magento.
2. Deactivate the paymentmethods from your stores administration in System->Configuration->Payment Methods
	* Set all payment methods to *Enabled: No*
3. Deactivate the module by changing true to false in the files:
	* app/etc/modules/SveaWebPay_Common.xml
	* app/etc/modules/SveaWebPay_Hosted.xml
	* app/etc/modules/SveaWebPay_HostedG.xml
	* app/etc/modules/SveaWebPay_Webservice.xml

```xml
<config>
	<modules>
		<SveaWebPay_Webservice>
                <!-- Change true to false here -->
			<active>false</active>
			<codePool>community</codePool>
			<depends>
				<Mage_Payment />
				<SveaWebPay_Common />
			</depends>
		</SveaWebPay_Webservice>
	</modules>
</config>
```

4. Install the new module
5. Configure settings

Please make sure the extensions *SOAP* and *OPENSSL* is aktivated for PHP. Also make sure to clean the cache of the store after installation and configuration is done.

Possibility to install this module from Magento Connect will be possible later on. If you choose to download it from here:
* Unzip the src folder and copy all subfolders into your stores *root* catalog. You will there find folders named *app*,*media*.
You will be asked to *replace* the folders. Choose *Copy and replace*, so all the folders merge together.
* You can also use the [**modman**](https://github.com/colinmollenhour/modman/wiki/Tutorial) script

## Configuration

###Invoice and Payment plan
The module lets you create Invoices, refund and cancel orders. If you choose **Autodeliver** in the module config, the order will automaticly be invoiced.

Notice that for Sveas systems the customer can only place orders for the same country as the store config.
If you have customers from different countries we recommend you have different store views with the different configvalues.

###Card and Direct
When card is choosen in the checkout, the customer will be redirected directly to our card payment provider for all our countries except from Denmark.
When direct bank (or card in Denmark) is choosen, the customer will be redirected to Svea WebPay PayPage. After payments completed, the customer will be redirected back to the store.

###Currency
Make sure you have the same currency set in your store as you account settings at Svea for Invoice and PartPayment.

###Error messages
The error message sent from our systems are translated into your language. If you want to change them with more instructions of what to do when something goes bad, you can manually change it in the csv-files.
ex.[**swedish**] https://github.com/sveawebpay/magento-module/blob/master/src/app/locale/sv_SE/Svea_WebPay.csv

####Cache
Mace sure to clear the cache in your store under System->Cache management.

## Important info
The request made from this module to SVEAs systems is made through a redirected form.
The response of the payment is then sent back to the module via POST or GET (selectable in our admin).

### When using GET
Have in mind that a long response string sent via GET could get cut off in some browsers and especially in some servers due to server limitations.
Our recommendation to solve this is to check the PHP configuration of the server and set it to accept at LEAST 512 characters.

### When using POST
As our servers are using SSL certificates and when using POST to get the response from a payment the users browser propmts the user with a question whether to continue or not, if the receiving site does not have a certificate.
Would the customer then click cancel, the process does not continue.  This does not occur if your server holds a certicifate. To solve this we recommend that you purchase a SSL certificate from your provider.

We can recommend the following certificate providers:
* InfraSec:  infrasec.se
* VeriSign : verisign.com

### Addresses
The Service integration method differentiates between a street and a c/o address. Since Magento only has support for multiple street lines but not their purpose, we can't validate and transfer the coAddress parameter to the Customer object.

## Deployment and version control
If you keep your Magento installation version-controlled, make sure that your /.gitignore has an entry for keeping the files inside /media/svea/*, otherwise deployment using tools like Capistrano won't include payment logotypes and loading images.
