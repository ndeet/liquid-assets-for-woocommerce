=== Liquid Assets for WooCommerce ===

Contributors: ndeet
Tags: WooCommerce, Liquid Network, Bitcoin, cryptocurrency
Requires at least: 4.9
Tested up to: 5.8
Requires PHP: 7.3
Stable tag: 1.8.3
License: MIT

Configure your products to reference your own Liquid Assets. The plugin will send Liquid Assets (coinos.io and your Elements RPC node supported) to customers after successful payment.


== Description ==

With this plugin you are able to sell your own special Liquid Assets to customers [(learn more about Liquid Network)](https://help.blockstream.com/hc/en-us/articles/900001543146-What-are-Liquid-assets-). Which they can then later use to pay for a product using [BTCPay Server WooCommerce plugin](https://wordpress.org/plugins/btcpay-for-woocommerce/). Think of it like a voucher or gift card.

You can configure Liquid Asset relationship on a per product basis and during checkout the user will be asked to enter their Liquid address. After payment has been completed the token will be sent to the user through coinos.io or your own Elements node.

= Plugin Configuration =
go to WooCommerce -> Liquid Assets Settings

**General:**
choose Elements RPC or coinos.io mode (depending on that continue with the matching section)
Enter the email recipients that should receive notifications in case of errors on either RPC or coinos mode. You can enter one or more separated by comma.

**Coinos.io:**
add your coinos.io API key (JWT token) here (can be found on coinos.io profile settings page)

**Elements RPC:**
RPC Host: enter the host or proxy accepting Elements RPC requests
RPC User: the configured RPC user (as configured in elements.conf)
RPC Pass: the password (as configured in elements.conf)

= Product configuration =

On the product edit form you have 2 new fields in the “General” data tab:
* **Liquid Asset ID:** enter Liquid Asset ID here you want to send to customer
* **Customer Liquid address:** the text entered here will be shown on the frontend product page as label.

= Product page / Add to cart form (frontend) styling =

If you enter above mentioned new product fields you will now see the label and a input text field on the product page, here the user can add their Liquid address.

The examples here apply for the default WordPress theme “twentytwentyone”. You might also need the following classes to adjust the styling of the input field and label for your custom theme.

**Example:**
`
.wcla-liquid-address-wrapper {
 margin-bottom: 30px;
}
 
.wcla-liquid-address-label {
 display: block;
}
 
input#wcla-liquid-address {
 width: 100%;
}
`

= Orders and orders notes =

On the orders line item you will see the users Liquid address in case anything went wrong with the coinos.io API. You will also get some order notes in case there was a problem with coinos.io and their API (or Elements RPC) response was not status 200 OK or the request timed out. If you configured the admin notification mails those recipients will get also emails in case there was an error.

Make also sure your coinos.io account / Elements node has enough funds of Liquid Assets and L-BTC for the fees to send.


== Installation ==

= Minimum Requirements =
* WooCommerce 4.5 or greater
* WordPress 5.2 or greater

= Automatic installation =
This is the easiest option as you can click through everything in the WordPress admin interface. Log in to your WordPress admin panel, navigate to the Plugins menu and click [Add New].
	
In the search field type "Liquid Assets for WooCommerce". You can install it by simply clicking [Install Now]. After clicking that link you will be asked if you're sure you want to install the plugin. Click yes and WordPress will automatically complete the installation. After installation has finished, click the [activate plugin] link.
	
= Manual installation via the WordPress interface =
1. Download the plugin zip file to your computer
2. Go to the WordPress admin panel menu Plugins > Add New
3. Choose upload
4. Upload the plugin zip file, the plugin will now be installed
5. After installation has finished, click the 'activate plugin' link
	
= Manual installation via FTP =
1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

== Changelog ==
= 1.8.3 :: 2021-08-26 =
* Added: Banner images for plugin directory page.

= 1.8.2 :: 2021-07-26 =
* Added: Icons for plugin directory
* Changed: Tested WP version 5.8.0 and changed supported version accordingly.

= 1.8.1 :: 2021-07-26 =
* Changed: Do not send admin notification mails if asset was already successfully sent.

= 1.8.0 :: 2021-07-26 =
Initial public release.
