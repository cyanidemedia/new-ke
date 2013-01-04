<?php
/*
Plugin Name: WooCommerce Google Checkout Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Extends WooCommerce. Provides a <a href="http://checkout.google.com" target="_blank">Google Checkout</a> gateway for WooCommerce. Email <a href="mailto:niklas@krokedil.se">niklas@krokedil.se</a> with any questions.
Version: 1.2.1
Author: Niklas Högefjord
Author URI: http://krokedil.com

	Copyright 2011  Niklas Högefjord  (email : niklas@krokedil.se)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Required functions
 **/
if ( ! function_exists( 'is_woocommerce_active' ) ) require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
if (is_admin()) {
	$woo_plugin_updater_googleco = new WooThemes_Plugin_Updater( __FILE__ );
	$woo_plugin_updater_googleco->api_key = '6fd7ea99664261c2e3582bfb84e50705';
	$woo_plugin_updater_googleco->init();
}

// Init Google Checkout Gateway after WooCommerce has loaded
add_action('plugins_loaded', 'init_googlecheckout_gateway', 0);

function init_googlecheckout_gateway() {
	
	// If the WooCommerce payment gateway class is not available, do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	define('GOOGLE_CHECKOUT_LIB', dirname(__FILE__) . '/library/');
	
	class WC_Google_Checkout extends WC_Payment_Gateway {
			
		public function __construct() { 
	        $this->id			= 'googlecheckout';
	        $this->method_title = __('Google Checkout', 'woothemes');
	        $this->icon 		= plugins_url(basename(dirname(__FILE__))."/images/google_checkout.png");
	        $this->has_fields 	= false;
	        
	        // Load the form fields.
			$this->init_form_fields();
			
			// Load the settings.
			$this->init_settings();
			
			// Define user set variables
	      	$this->enabled			= $this->settings['enabled'];
			$this->title 			= $this->settings['title'];
			$this->description  	= $this->settings['description'];
			$this->merchant_id		= $this->settings['merchant_id'];
			$this->merchant_key		= $this->settings['merchant_key'];
			$this->testmode			= $this->settings['testmode'];
			
			// Actions
			add_action( 'init', array(&$this, 'check_googlecheckout_response') );
			add_action( 'valid-googlecheckout-request', array(&$this, 'successful_request') );
			add_action( 'woocommerce_receipt_googlecheckout', array(&$this, 'receipt_page') );
			add_action( 'woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options') );
	    } 

		/**
    	 * Initialise Gateway Settings Form Fields
    	 */
    	function init_form_fields() {
    	
    		$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'woothemes' ), 
								'type' => 'checkbox', 
								'label' => __( 'Enable Google Checkout', 'woothemes' ), 
								'default' => 'yes'
							), 
				'title' => array(
								'title' => __( 'Title', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
								'default' => __( 'Google Checkout', 'woothemes' )
							),
				'description' => array(
								'title' => __( 'Description', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
								'default' => __( 'Pay via Credit Card with Google Checkout secure card processing.', 'woothemes' ),
							),
				'merchant_id' => array(
								'title' => __( 'Merchant ID', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Please enter your Google Checkout merchant ID; this is needed in order to take payment!', 'woothemes' ), 
								'default' => __( '', 'woothemes' )
							),
				'merchant_key' => array(
								'title' => __( 'Merchant Key', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Please enter your Google Checkout merchant Key; this is needed in order to take payment!', 'woothemes' ), 
								'default' => __( '', 'woothemes' )
							),
				'testmode' => array(
								'title' => __( 'Google Checkout sandbox', 'woothemes' ), 
								'type' => 'checkbox', 
								'label' => __( 'Enable Google Checkout Sandbox.', 'woothemes' ), 
								'default' => 'no'
							)/*, 
				'debugmode' => array(
								'title' => __( 'Send Debug Emails', 'woothemes' ), 
								'type' => 'checkbox', 
								'label' => __( 'Enable Send Debug Emails. Receive email containing data sent to Google Checkout and data returned from Google Checkout.', 'woothemes' ), 
								'default' => 'no'
							), 
				'debugmode_email' => array(
								'title' => __( 'Who gets the Debug emails', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'If Send Debug Emails is active, where do we send the emails?', 'woothemes' ), 
								'default' => get_bloginfo('admin_email')
							) */
				);
    
		} // End init_form_fields()
		
		
		/**
	 	* Admin Panel Options 
	 	* - Options for bits like 'title' and availability on a country-by-country basis
	 	*
	 	* @since 1.0.0
	 	*/
		public function admin_options() {
	
	    	?>
	    	<?php $callbackUrl = add_query_arg( 'googleCheckoutListener', '1', trailingslashit( site_url() ) ); ?>
	    	<h3><?php _e('Google Checkout', 'woothemes'); ?></h3>
	    	<p><?php _e('Google Checkout works by sending the user to <a href="http://checkout.google.com">Google Checkout</a> to enter their payment information.', 'woothemes'); ?></p>
	    	<p><?php echo sprintf(__('Please configure your Google Checkout merchant account as follows (you can find this under --> Settings --> Integration): <ul><li><em>My company will only post digitally signed carts</em> option must NOT be checked. </li><li><em>API callback URL</em> must be set to <em>%s</em>.</li><li> <em>Callback contents</em> must be set to <em>Notification as XML</em>.</li><li><em>API Version</em> must be set to <em>Version 2.0</em>.</li></ul>', 'woothemes'), $callbackUrl); ?></p>
    		<table class="form-table">
    		<?php
    			// Generate the HTML For the settings form.
    			$this->generate_settings_html();
    		?>
			</table><!--/.form-table-->
    		<?php
    	} // End admin_options()
    
	    
	    /**
		 * There are no payment fields for Google Checkout, but we want to show the description if set.
		 **/
		function payment_fields() {
			if ($this->description) echo wpautop(wptexturize($this->description));
		}
	    
	    	    
		/**
	 	* Generate the Google Checkout button link
	 	**/
		public function generate_googlecheckout_form( $order_id ) {
			global $woocommerce;
			
			require_once(GOOGLE_CHECKOUT_LIB . 'googlecart.php');
			require_once(GOOGLE_CHECKOUT_LIB . 'googleitem.php');
			require_once(GOOGLE_CHECKOUT_LIB . 'googleshipping.php');
			require_once(GOOGLE_CHECKOUT_LIB . 'googletax.php');
			
			$order = new WC_Order( $order_id );
			
			$shipping_name = explode(' ', $order->shipping_method);
			
			// Check if this is a test purchase
			if ( $this->testmode == 'yes' ):
				$server_type = "sandbox";		
			else :
				$server_type = "checkout";		
			endif;
			
			$merchant_id = $this->merchant_id;  // Your Merchant ID
      		$merchant_key = $this->merchant_key;  // Your Merchant Key
      		$currency = get_option('woocommerce_currency');
      		
      		$cart = new GoogleCart($merchant_id, $merchant_key, $server_type, $currency);
   
       		// Specify <edit-cart-url>
      		$cart->SetEditCartUrl( get_permalink(get_option('woocommerce_cart_page_id')) );
      
      		// Specify "Return to xyz" link
      		$cart->SetContinueShoppingUrl( add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id')))) );
      		
      		// Order key
      		$cart->SetMerchantPrivateData(
              new MerchantPrivateData(array("cart-id" => $order->id )));
              
       		// Request buyer's phone number
      		$cart->SetRequestBuyerPhone(true);

      		
      		// Default tax  - for shipping, if used
      		if ($order->order_shipping_tax>0) :
      			// We manually calculate the shipping tax percentage here
				$calculated_shipping_tax_percentage = $order->order_shipping_tax/$order->order_shipping;		
      			
      			$tax_rule_for_shipping = new GoogleDefaultTaxRule($calculated_shipping_tax_percentage, 'true');
    			$tax_rule_for_shipping->SetWorldArea(true);
    			$cart->AddDefaultTaxRules($tax_rule_for_shipping);
      		endif;
      		
      		
      		// Shipping Cost
			if ($order->order_shipping>0) :
			
				$ship_1 = new GoogleFlatRateShipping(
					$order->shipping_method, 
					number_format($order->order_shipping, 2) 
				);
	    		
	    		$restriction_1 = new GoogleShippingFilters();
	    		$restriction_1->SetAllowedWorldArea(true);
	    		$ship_1->AddShippingRestrictions($restriction_1);
	    		$cart->AddShipping($ship_1);
    
			endif;
  	
			// Cart Contents
			$item_loop = 0;
			$myvat = array();
			if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
				$_product = new WC_Product($item['id']);
				
				if ($_product->exists() && $item['qty']) :
					$item_loop++;
					// Change tax format from 25.00 to 0.25
					$item_tax_percentage = number_format( ( $order->get_item_tax($item, false) / $order->get_item_total( $item, false, false ) )*100, 2, '.', '');
					$item_vat = $item_tax_percentage/100;
					$myvat[$item_loop] = $item_vat;
					
					${"item_".$item_loop} = new GoogleItem(
						$item['name'],								// Item name
						"", 										// Item description
						$item['qty'], 								// Quantity
						$order->get_item_total( $item, false, false )		// Unit price
					);
					
					// Name the alternate-tax-table
					$vat_name = "vat" . $item_vat;
					
					${"item_".$item_loop}->SetMerchantItemId($item['id']);
					${"item_".$item_loop}->SetTaxTableSelector($vat_name);
      				$cart->AddItem(${"item_".$item_loop});		
									
				endif;
			endforeach; endif;
			

			
			// Discount
			if ($order->order_discount>0) :
				$item_loop++;
				
				${"item_".$item_loop} = new GoogleItem(
					__('Discount', 'woothemes'),		// Item name
					"", 								// Item description
					"1", 								// Quantity
					-$order->order_discount 			// Unit price
				);
				${"item_".$item_loop}->SetTaxTableSelector("no_tax");
   				$cart->AddItem(${"item_".$item_loop});	
   							
			endif;
			
			    
    		// Tax
    		// Loops through all tax classes that has been added to the cart and add these as Alternate tax tables to google Checkout.
    		$taxrule_loop = 1;
    		$no_duplicate_vat = array_unique($myvat);
    
    		foreach ($no_duplicate_vat as $value) :
    			// Name the alternate-tax-table
				$vat_name = "vat" . $value;
		
    			$tax_table = new GoogleAlternateTaxTable( $vat_name );
    			${"tax_rule_".$taxrule_loop} = new GoogleAlternateTaxRule( $value );
    			${"tax_rule_".$taxrule_loop}->SetWorldArea(true);
    			$tax_table->AddAlternateTaxRules( ${"tax_rule_".$taxrule_loop} );
    			$cart->AddAlternateTaxTables($tax_table);
    			$taxrule_loop++;
    		endforeach; 
		
			
			// The form		
			return 	// Display Google Checkout button
      				$cart->CheckoutButtonCode("SMALL") .
      				
      				'<script type="text/javascript">
						jQuery(function(){
							jQuery("body").block(
								{ 
									message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Thank you for your order. We are now redirecting you to Google Checkout to make payment.', 'woothemes').'", 
									overlayCSS: 
									{ 
										background: "#fff", 
										opacity: 0.6 
									},
									css: { 
                                   		padding:        20, 
                                   		textAlign:      "center", 
                                   		color:          "#555", 
                                   		border:         "3px solid #aaa", 
                                   		backgroundColor:"#fff", 
                                   		cursor:         "wait",
                                   		lineHeight:        "32px"
                               		} 
								});
							jQuery("#submit_googlecheckout_payment_form").click();
						});
					</script>'; 
		}
		
		/**
		 * Process the payment and return the result
		 **/
		function process_payment( $order_id ) {
			
			$order = new WC_Order( $order_id );
			
			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
			);
			
		}
		
		/**
		 * receipt_page
		 **/
		function receipt_page( $order ) {
			
			echo '<p>'.__('Thank you for your order, please click the button below to pay with Google Checkout.', 'woothemes').'</p>';
			
			echo $this->generate_googlecheckout_form( $order );
		}
		
		
		/**
		 * Check for Google Checkout Response
		 **/
		function check_googlecheckout_response() {
			
			if (isset($_GET['googleCheckoutListener']) && $_GET['googleCheckoutListener'] == '1'):
            	
				require_once(GOOGLE_CHECKOUT_LIB . 'googleresponse.php');
  				require_once(GOOGLE_CHECKOUT_LIB . 'googlemerchantcalculations.php');
  				require_once(GOOGLE_CHECKOUT_LIB . 'googleresult.php');
  				require_once(GOOGLE_CHECKOUT_LIB . 'googlerequest.php');
  
				define('RESPONSE_HANDLER_ERROR_LOG_FILE', 'googleerror.log');
  				define('RESPONSE_HANDLER_LOG_FILE', 'googlemessage.log');

  				$merchant_id = $this->merchant_id;  // Your Merchant ID
      			$merchant_key = $this->merchant_key;  // Your Merchant Key
      			$currency = get_option('woocommerce_currency');
      			
      			// Check if this is a test purchase
				if ( $this->testmode == 'yes' ):
					$server_type = "sandbox";		
				else :
					$server_type = "checkout";		
				endif;
      			
  				$Gresponse = new GoogleResponse($merchant_id, $merchant_key);
  				$Grequest = new GoogleRequest($merchant_id, $merchant_key, $server_type, $currency);

  				//Setup the log file
  				$Gresponse->SetLogFiles('', '', L_OFF);  //Change this to L_ON to log

  				// Retrieve the XML sent in the HTTP POST request to the ResponseHandler
  				$xml_response = isset($HTTP_RAW_POST_DATA)?
  					$HTTP_RAW_POST_DATA:file_get_contents("php://input");
  				if (get_magic_quotes_gpc()) {
  					$xml_response = stripslashes($xml_response);
  				}
  				
  				list($root, $data) = $Gresponse->GetParsedXML($xml_response);
  				$Gresponse->SetMerchantAuthentication($merchant_id, $merchant_key);
  
  				$googlecheckout_return_values = array(
					"RefNr" 				=> $data[$root]['shopping-cart']['merchant-private-data']['cart-id']['VALUE' ],
					"google_order_number" 	=> $data[$root]['google-order-number']['VALUE'],
					"financial_order_state" 	=> $data[$root]['financial-order-state']['VALUE']
				);
				
  				switch($root){
  					case "new-order-notification": {
  						if ( isset($googlecheckout_return_values['google_order_number']) ) :
  							do_action("valid-googlecheckout-request", $googlecheckout_return_values);
  						endif;
  						$Gresponse->SendAck();
  					break;
  					}
				}

			
			endif;								
		}

		
		/**
	 	* Successful Payment!
	 	**/
		function successful_request( $googlecheckout_return_values ) {
			global $woocommerce;
			
		    if ( !empty($googlecheckout_return_values['RefNr']) && !empty($googlecheckout_return_values['financial_order_state']) ) {
		    
		    	$accepted_status = array('REVIEWING', 'CHARGEABLE', 'CHARGING', 'CHARGED', 'PAYMENT_DECLINED', 'CANCELLED', 'CANCELLED_BY_GOOGLE');
				if (!in_array($googlecheckout_return_values['financial_order_state'], $accepted_status)) exit;
		    
				$order_id 	  			= $googlecheckout_return_values['RefNr'];
				$google_order_number	= $googlecheckout_return_values['google_order_number'];
				$financial_order_state	= $googlecheckout_return_values['financial_order_state'];
				$order 					= new WC_Order( (int) $order_id );
				$order_key				= $order->order_key;
				
				if ($order->status !== 'completed') :
		        	// We are here so lets check status and do actions
		        	switch (strtolower($financial_order_state)) :
		        	    case 'reviewing' :
		        	    case 'chargable' :
		        	    case 'charging' :
		        	    case 'charged' :
		        	    	// Payment completed
		        	        $order->add_order_note( __('Google Checkout payment completed. Order number: ', 'woothemes'). $google_order_number );
		        	        $order->payment_complete();
		        	    break;
		        	    case 'payment_declined' :
		        	    case 'cancelled' :
		        	    case 'cancelled_by_google' :
		        	        // Order failed
		        	        $order->update_status('failed', sprintf(__('Payment %s via Google Checkout.', 'woothemes'), strtolower($financial_order_state) ) );
		        	    break;
		        	    default:
		        	    	// No action
		        	    break;
		        	endswitch;
				endif;
			
				exit;		

		    }
		
		}

	} // Close class woocommerce_googlecheckout
	

} // Close init_googlecheckout_gateway

/**
 * Add the gateway to WooCommerce
 **/
function add_googlecheckout_gateway( $methods ) {
	$methods[] = 'WC_Google_Checkout'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_googlecheckout_gateway' );