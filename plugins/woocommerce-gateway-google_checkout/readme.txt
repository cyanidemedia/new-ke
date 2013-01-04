=== WOOCOMMERCE GOOGLE CHECKOUT GATEWAY ===
By Niklas Högefjord - http://krokedil.com/
Based on PayPal Standard Gateway by WooCommerce


== DESCRIPTION ==
Google Checkout Gateway is a plugin that extends WooCommerce, allowing you to take payments via Google Checkout.

Because WooCommerce calculates tax and shipping, this plugin send that information to Google Checkout. Any tax and shipping rules created in your Google Checkout merchant account will NOT be used. 


== Important Note ==
You *must* enable SSL from the settings panel to use this plugin in live mode - this is for your customers safety and for the Google Checkout notification callback to function properly.


== Installation ==
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go WooCommerce Settings --> Payment Gateways and configure your Google Checkout settings.
6. Configure your Google Checkout merchant account as follows (you can find this under --> Settings --> Integration):
	- My company will only post digitally signed carts option must NOT be checked.
	- API callback URL must be set to http://krokedil.nu/?googleCheckoutListener=1.
	- Callback contents must be set to Notification as XML.
	- API Version must be set to Version 2.0.


== Sandbox feature ==
You can test the gateway by enable Google Checkout Sandbox in the payment gateway settings. Sandbox mode does not require you to enable SSL.
To use the sandbox feature you need to create a sandbox buyer account (https://sandbox.google.com/checkout) and a sandbox merchant account (https://sandbox.google.com/checkout/sell).