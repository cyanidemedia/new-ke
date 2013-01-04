<?php
/*
  Plugin Name: WooCommerce UPS
  Description: Realtime shipping rates from UPS
  Version: 1.0.8
  Author: Andy Zhang
  Author URI: http://hypnoticzoo.com/

  Copyright: © 2009-2011 WooThemes.
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

add_action('plugins_loaded', 'woocommerce_ups_init', 0);

function woocommerce_ups_init() {

	include_once(plugin_dir_path(__FILE__) . 'hypnoticzoo_utilities.php');

	if (!class_exists('WC_Shipping_Method'))
		return;

	/**
	 * Required functions
	 * */
	if (!function_exists('is_woocommerce_active'))
		require_once( 'woo-includes/woo-functions.php' );

	/**
	 * Plugin updates
	 * */
	if (is_admin()) {
		$woo_plugin_updater_ups = new WooThemes_Plugin_Updater(__FILE__);
		$woo_plugin_updater_ups->api_key = '38178e64a83570b30b29ec5c60316d1b';
		$woo_plugin_updater_ups->init();
	}

	/**
	 * Shipping method class
	 * */
	class WC_Shipping_UPS extends WC_Shipping_Method {

		var $url = "https://www.ups.com/ups.app/xml/Rate";
		//var $test_url = "https://wwwcie.ups.com/ups.app/xml/Rate";
		var $codes = array(
		    "01" => "UPS Next Day Air",
		    "02" => "UPS Second Day Air",
		    "03" => "UPS Ground",
		    "07" => "UPS Worldwide Express",
		    "08" => "UPS Worldwide Expedited",
		    "11" => "UPS Standard",
		    "12" => "UPS Three-Day Select",
		    "13" => "UPS Next Day Air Saver",
		    "14" => "UPS Next Day Air Early A.M.",
		    "54" => "UPS Worldwide Express Plus",
		    "59" => "UPS Second Day Air A.M.",
		    "65" => "UPS Saver",
		    "82" => "UPS Today Standard",
		    "83" => "UPS Today Dedicated Courrier",
		    "84" => "UPS Today Intercity",
		    "85" => "UPS Today Express",
		    "86" => "UPS Today Express Saver");
		var $worldwide = array("07", "08", "11", "54", "65");
		var $services = array(
		    "US" => array("01", "02", "03", "07", "08", "11", "12", "13", "14", "54", "59", "65"),
		    "PR" => array("01", "02", "03", "07", "08", "14", "54", "65"),
		    "CA" => array("01", "02", "07", "08", "11", "12", "13", "14", "54", "65"),
		    "MX" => array("07", "08", "11", "54", "65"),
		    "PL" => array("07", "08", "11", "54", "65", "82", "83", "84", "85", "86"));
		var $packing_options = array(
		    "shipping_per_item" => array(
			"type" => "Shipping Per Item"
		    ),
		    "express_large" => array(
			"type" => "UPS Express Box Large",
			"volume" => 702,
			"width" => 18,
			"height" => 13,
			"length" => 3,
			"weight_limit" => 0,
		    ),
		    "express_medium" => array(
			"type" => "UPS Express Box Medium",
			"volume" => 495,
			"width" => 15,
			"height" => 11,
			"length" => 3,
			"weight_limit" => 0,
		    ),
		    "express_small" => array(
			"type" => "UPS Express Box Small",
			"volume" => 286,
			"width" => 13,
			"height" => 11,
			"length" => 2,
			"weight_limit" => 0,
		    ),
		    "express_tube" => array(
			"type" => "UPS Express Tube",
			"volume" => 1368,
			"width" => 38,
			"height" => 6,
			"length" => 6,
			"weight_limit" => 0,
		    ),
		    "world_ease_document" => array(
			"type" => "UPS World Ease Document Box",
			"volume" => 656.25,
			"width" => 17.5,
			"height" => 12.5,
			"length" => 3,
			"weight_limit" => 0,
		    ),
		    "10kg_box" => array(
			"type" => "UPS 10KG Box",
			"volume" => 2350,
			"width" => 16.5,
			"height" => 13.25,
			"length" => 10.75,
			"weight_limit" => 22,
		    ),
		    "25kg_box" => array(
			"type" => "UPS 25KG Box",
			"volume" => 4712,
			"width" => 19.375,
			"height" => 17.375,
			"length" => 14,
			"weight_limit" => 55,
		    ),
		    "custom_box" => array(
			"type" => "Custom Box"
		    )
		);

		function __construct() {
			global $woocommerce;
			$this->id = 'ups';
			$this->method_title = __('UPS', 'woothemes');
			$this->xmlparser = new ArrayToXML();

			$this->origin_country = $woocommerce->countries->get_base_country();

			// Methods/services
			if (array_key_exists($this->origin_country, $this->services))
				$services = $this->services[$this->origin_country];
			else
				$services = $this->worldwide;

			// Build the service list
			$this->available_services = array();

			foreach ($services as $code)
				$this->available_services[$code] = $this->codes[$code];

			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			$this->enabled = $this->settings['enabled'];
			$this->title = $this->settings['title'];

			$this->availability = $this->settings['availability'];
			$this->origin = $this->settings['origin'];
			$this->countries = $this->settings['countries'];
			$this->measure_unit = $this->settings['measure_unit'];
			$this->tax_status = $this->settings['tax_status'];
			$this->fee = $this->settings['fee'];
			$this->user_id = $this->settings['user_id'];
			$this->password = $this->settings['password'];
			$this->shipper = $this->settings['ship_number'];
			$this->pickup = $this->settings['pickup'];
			$this->packing = $this->settings['packing'];
			$this->custom_box_width = $this->settings['custom_box_width'];
			$this->custom_box_length = $this->settings['custom_box_length'];
			$this->custom_box_height = $this->settings['custom_box_height'];
			$this->custom_box_weight = $this->settings['custom_box_weight'];
			$this->packing = $this->settings['packing'];
			$this->access_code = $this->settings['access_code'];
			$this->shipping_availability = $this->settings['shipping_availability'];
			$this->debug = (!empty($this->settings['debug']) && $this->settings['debug'] == 'yes') ? true : false;

			// Load the shipping method/services that are enabled
			if ($this->settings['shipping_availability'] == 'specific' && is_array($this->settings['shipping_methods'])) {

				foreach ($this->available_services as $key => $code)
					if (in_array($key, $this->settings['shipping_methods']))
						$this->shipping_methods[$key] = $code;
			} else {
				$this->shipping_methods = $this->available_services;
			}

			add_action('woocommerce_update_options_shipping_ups', array(&$this, 'process_admin_options'));
			add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_admin_options'));
			add_action('admin_notices', array(&$this, 'currency_check'));
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 */
		function init_form_fields() {
			global $woocommerce;
			$packages = array();
			foreach ($this->packing_options as $type => $detail) {
				$packages[$type] = $detail['type'];
			}

			$this->form_fields = array(
			    'enabled' => array(
				'title' => __('Enable/Disable', 'woothemes'),
				'type' => 'checkbox',
				'label' => __('Enable UPS', 'woothemes'),
				'default' => 'yes'
			    ),
			    'title' => array(
				'title' => __('Method Title', 'woothemes'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'woothemes'),
				'default' => __('UPS', 'woothemes')
			    ),
			    'debug' => array(
				'title' => __('Debug Mode', 'woothemes'),
				'label' => __('Enable Debug Mode', 'woothemes'),
				'type' => 'checkbox',
				'description' => __('Output the response from UPS on the cart/checkout for debugging purposes.', 'woothemes'),
				'default' => 'no'
			    ),
			    'origin' => array(
				'title' => __('Origin Zipcode', 'woothemes'),
				'type' => 'text',
				'description' => __('Enter your origin zip code.', 'woothemes'),
				'default' => __('', 'woothemes')
			    ),
			    'tax_status' => array(
				'title' => __('Tax Status', 'woothemes'),
				'type' => 'select',
				'description' => '',
				'default' => 'taxable',
				'options' => array(
				    'taxable' => __('Taxable', 'woothemes'),
				    'none' => __('None', 'woothemes')
				)
			    ),
			    'fee' => array(
				'title' => __('Handling Fee', 'woothemes'),
				'type' => 'text',
				'description' => __('Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%.', 'woothemes'),
				'default' => '0'
			    ),
			    'user_id' => array(
				'title' => __('User Name', 'woothemes'),
				'type' => 'text',
				'description' => __('Your UPS user name', 'woothemes'),
				'default' => ''
			    ),
			    'password' => array(
				'title' => __('Password', 'woothemes'),
				'type' => 'text',
				'description' => __('Your UPS password', 'woothemes'),
				'default' => ''
			    ),
			    'access_code' => array(
				'title' => __('License Number', 'woothemes'),
				'type' => 'text',
				'description' => __('UPS Access License Number', 'woothemes'),
				'default' => ''
			    ),
			    'ship_number' => array(
				'title' => __('Shipper Number', 'woothemes'),
				'type' => 'text',
				'description' => __('UPS Shipper Number', 'woothemes'),
				'default' => ''
			    ),
			    'pickup' => array(
				'title' => __('Pick Up Option', 'woothemes'),
				'type' => 'select',
				'default' => '01',
				'options' => array(
				    '01' => 'Daily Pickup',
				    '03' => 'Customer Counter',
				    '06' => 'One Time Pickup',
				    '07' => 'On Call Air',
				    '11' => 'Suggested Retail Rates',
				    '19' => 'Letter Center',
				    '20' => 'Air Service Center'
				)
			    ),
			    'packing' => array(
				'title' => __('Packing', 'woothemes'),
				'type' => 'select',
				'default' => '',
				'options' => $packages
			    ),
			    'custom_box_width' => array(
				'title' => __('Custom box width', 'woothemes'),
				'type' => 'text',
				'class' => 'custom_box',
				'default' => ''
			    ),
			    'custom_box_length' => array(
				'title' => __('Custom box length', 'woothemes'),
				'type' => 'text',
				'class' => 'custom_box',
				'default' => ''
			    ),
			    'custom_box_height' => array(
				'title' => __('Custom box height', 'woothemes'),
				'type' => 'text',
				'class' => 'custom_box',
				'default' => ''
			    ),
			    'custom_box_weight' => array(
				'title' => __('Custom box weight', 'woothemes'),
				'type' => 'text',
				'class' => 'custom_box',
				'default' => ''
			    ),
			    'measure_unit' => array(
				'title' => __('Measure Unit', 'woothemes'),
				'type' => 'select',
				'description' => __('Package measurement unit, in pair of Kg/CM or Lbs/Inch.', 'woothemes'),
				'default' => '2',
				'options' => array(
				    '1' => 'Kg/CM',
				    '2' => 'Lbs/Inch',
				)
			    ),
			    'shipping_availability' => array(
				'title' => __('Shipping method availability', 'woothemes'),
				'type' => 'select',
				'default' => 'all',
				'class' => 'availability',
				'options' => array(
				    'all' => __('All allowed methods', 'woothemes'),
				    'specific' => __('Specific methods', 'woothemes')
				)
			    ),
			    'shipping_methods' => array(
				'title' => __('Specific Shipping Methods', 'woothemes'),
				'type' => 'multiselect',
				'class' => 'chosen_select',
				'css' => 'width: 450px;',
				'default' => '',
				'options' => $this->available_services
			    ),
			    'availability' => array(
				'title' => __('Method Availability', 'woothemes'),
				'type' => 'select',
				'default' => 'all',
				'class' => 'availability',
				'options' => array(
				    'all' => __('All allowed countries', 'woothemes'),
				    'specific' => __('Specific Countries', 'woothemes')
				)
			    ),
			    'countries' => array(
				'title' => __('Specific Countries', 'woothemes'),
				'type' => 'multiselect',
				'class' => 'chosen_select',
				'css' => 'width: 450px;',
				'default' => '',
				'options' => $woocommerce->countries->countries
			    )
			);
		}

		/**
		 * First step is to make sure the configuration of woocommerce is correct.
		 * Raise warning if is not correct.
		 */
		function currency_check() {

			if (!$this->origin && $this->enabled == 'yes') :

				echo '<div class="error"><p>' . sprintf(__('UPS is enabled, but the <a href="%s">origin zip code</a> is not available.', 'woothemes'), admin_url('admin.php?page=woocommerce&tab=shipping&subtab=shipping-ups')) . '</p></div>';

			endif;
		}

		/**
		 * Shipping method available condition:
		 * 1. Set to yes
		 * 2. Currency is USD
		 * 3. Origin country is US
		 * 4. Dest country is in the list
		 * 
		 * @global type $woocommerce
		 * @return type 
		 */
		function is_available() {
			global $woocommerce;

			if ($this->enabled == "no")
				return false;

			/* if (isset($woocommerce->cart->cart_contents_total) && isset($this->min_amount) && $this->min_amount && $this->min_amount > $woocommerce->cart->cart_contents_total)
			  return false; */

			if (!$this->origin):
				return false;
			endif;

			$ship_to_countries = '';

			if ($this->packing == "custom_box" && (!$this->custom_box_height || !$this->custom_box_length || !$this->custom_box_width))
				return false;

			if ($this->availability == 'specific') :
				$ship_to_countries = $this->countries;
			else :
				if (get_option('woocommerce_allowed_countries') == 'specific') :
					$ship_to_countries = get_option('woocommerce_specific_allowed_countries');
				endif;
			endif;

			if (is_array($ship_to_countries)) :
				if (!in_array($woocommerce->customer->get_shipping_country(), $ship_to_countries))
					return false;
			endif;

			return true;
		}

		/**
		 * Get shipping quotes based on change of shipping address
		 * This function orgnized shipping quotes to be used
		 * 
		 * @method calculate_shipping
		 * @abstract setup shipping rate for each selected shipping option
		 */
		function calculate_shipping() {
			global $woocommerce;

			$customer = $woocommerce->customer;
			$update_rates = false;
			$cart_items = $woocommerce->cart->get_cart();
			foreach ($cart_items as $id => $cart_item) {
				$cart_temp[] = $id . $cart_item['quantity'];
			}
			$cart_hash = hash('MD5', serialize($cart_temp));

			if (!$this->debug)
				$cache_data = get_transient(get_class($this));
			else
				$cache_data = '';

			if ($cache_data) {
				if ($cache_data['cart_hash'] == $cart_hash && $cache_data['shipping_data']['postalcode'] == $customer->get_shipping_postcode() && $cache_data['shipping_data']['State'] == $customer->get_shipping_state() && $cache_data['shipping_data']['Country'] == $customer->get_shipping_country()) {
					$this->rates = $cache_data['rates'];
				} else {
					$update_rates = true;
				}
			} else {
				$update_rates = true;
			}

			//only update rates when needed
			if ($update_rates) {
				$quotes = $this->get_shipping_response();
				if ($quotes) {
					foreach ($quotes as $quote) {
						$this->add_rate($quote);
					}
					$cache_data['shipping_data'] = array(
					    'postalcode' => $customer->get_shipping_postcode(),
					    'State' => $customer->get_shipping_state(),
					    'Country' => $customer->get_shipping_country()
					);
					$cache_data['cart_hash'] = $cart_hash;
					$cache_data['rates'] = $this->rates;

					set_transient(get_class($this), $cache_data);
				}
			}
		}

		/**
		 * Where we actually calculation shipping quotes and return response
		 * by using encode and decode
		 */
		function get_shipping_response() {
			global $woocommerce;

			$base_result = array();
			$debug_response = array();

			foreach ($this->shipping_methods as $key => $service) {
				$request = $this->encode($key);
				if ($request) {
					$response = wp_remote_post($this->url, array(
					    'method' => 'POST',
					    'body' => $request,
					    'timeout' => 70,
					    'sslverify' => 0
						));
					if (!is_wp_error($response)) {
						$result = $this->decode($response['body']);
						if ($result) {
							$base_result[] = $result;
							$debug_response[] = $service . ' - ' . 'SUCCESS';
						} else {
							$debug_response[] = 'No rate for ' . $service . ' - ' . print_r($response['body'], true);
						}
					} else {
						$debug_response[] = 'Response failed for ' . $service;
					}
				}
			}

			if ($this->debug && $debug_response) {
				$woocommerce->clear_messages();
				$woocommerce->add_message('<p>UPS Response:</p><ul><li>' . implode('</li><li>', $debug_response) . '</li></ul>');
			}

			return (count($base_result)) ? $base_result : false;
		}

		/**
		 * Encode the request
		 */
		function encode($service_code) {
			global $woocommerce;

			$customer = $woocommerce->customer;
			$encode = '';
			$packing = $this->packing;
			$shipping_per_item = $packing == "shipping_per_item";

			/**
			 * encode xml, the request allows max 200 packages, we still keep track of the package count and create request batch
			 */
			if (sizeof($woocommerce->cart->get_cart()) > 0 && ($customer->get_shipping_country())) {
				$access = array(
				    "AccessLicenseNumber" => $this->access_code,
				    "UserId" => $this->user_id,
				    "Password" => $this->password,
				);

				$request = array(
				    "Request" => array(
					"TransactionReference" => array(
					    "CustomerContext" => "WooCommerce UPS packages",
					    "XpciVersion" => "1.0001"
					),
					"RequestAction" => "Rate",
					"RequestOption" => "Rate",
				    ),
				    "PickupType" => array(
					"Code" => $this->pickup
				    ),
				    "CustomerClassification" => array(
					"Code" => ($this->pickup == "01") ? $this->pickup : ((in_array($this->pickup, array("06", "07", "19", "20"))) ? "04" : "53")
				    ),
				    "Shipment" => array(
					"Description" => "WooCommerce UPS packages",
					"Shipper" => array(
					    "ShipperNumber" => $this->shipper,
					    "Address" => array(
						"PostalCode" => $this->origin,
						"CountryCode" => $this->origin_country
					    )
					),
					"ShipTo" => array(
					    "Address" => array(
						"StateProvinceCode" => $customer->get_shipping_state(),
						"PostalCode" => $customer->get_shipping_postcode(),
						"CountryCode" => $customer->get_shipping_country(),
						"ResidentialAddressIndicator" => ""
					    ),
					),
					"ShipFrom" => array(
					    "Address" => array(
						"PostalCode" => $this->origin,
						"CountryCode" => $this->origin_country
					    )
					),
					"Service" => array(
					    "Code" => $service_code
					),
				    )
				);
				if ($shipping_per_item) {
					$request["Shipment"]["Package"] = $this->packing_perItem($woocommerce->cart->get_cart());
				} else {
					$request["Shipment"]["Package"] = $this->packing_perOrder($woocommerce->cart->get_cart());
				}
				$access_xml = $this->xmlparser->toXML($access, "AccessRequest");
				$request_xml = $this->xmlparser->toXML($request, "RatingServiceSelectionRequest");
				$encode = $access_xml . $request_xml;
			}
			return $encode;
		}

		/**
		 * Decode the result
		 */
		function decode($response) {
			global $woocommerce;
			$rate = array();
			$response = $this->xmlparser->toArray($response);
			if (!is_array($response))
				return false;

			if ($response['Response']['ResponseStatusCode'] == "1") {
				$rate_response = $response['RatedShipment'];

				$rate['id'] = "ups_" . $rate_response['Service']['Code'] . ' - ' . $this->codes[$rate_response['Service']['Code']];
				$rate['label'] = $this->codes[$rate_response['Service']['Code']];
				$rate['cost'] = $rate_response['TotalCharges']['MonetaryValue'] + $this->get_fee($this->fee, $woocommerce->cart->cart_contents_total);
				$rate['calc_tax'] = "per_order";
			}
			return count($rate) ? $rate : false;
		}

		/**
		 * packing order for per-order shipping
		 */
		function packing_perOrder($cart) {

			$packing_option = $this->packing_options[$this->packing];
			$product_bucket = array();
			$weights = array();
			$volume = 0;
			$acc_weight = 0;
			$box = 0;

			if ($this->measure_unit === "1") {
				$wu = "kg";
				$du = "cm";
			} elseif ($this->measure_unit === "2") {
				$wu = "lbs";
				$du = "in";
			}
			if ($this->packing != "custom_box") {
				$box_volume = ($du == "cm") ? $packing_option["volume"] * 16.38 : $packing_option["volume"];
				$box_weight = ($wu == "kg") ? $packing_option["weight_limit"] * 0.45 : $packing_option["weight_limit"];
				$width = ($du == "cm") ? $packing_option["width"] * 2.54 : $packing_option["width"];
				$length = ($du == "cm") ? $packing_option["length"] * 2.54 : $packing_option["length"];
				$height = ($du == "cm") ? $packing_option["height"] * 2.54 : $packing_option["height"];
			} else {
				$box_volume = $this->custom_box_height * $this->custom_box_length * $this->custom_box_width;
				$box_weight = $this->custom_box_weight;
				$width = $this->custom_box_width;
				$length = $this->custom_box_length;
				$height = $this->custom_box_height;
			}

			foreach ($cart as $values) {

				$_product = $values['data'];

				if ($_product->exists() && $values['quantity'] > 0) {

					if (!$_product->is_virtual()) {
						for ($i = 0; $i < $values['quantity']; $i++) {
							$volume += $this->product_volume($_product, $du);
							$weight = $_product->get_weight();
							if ($volume < $box_volume && (!$box_weight || $acc_weight + $weight <= $box_weight)) {
								$acc_weight += $weight;
							} else {
								$box++;
								$weights[$box] = $acc_weight;
								$acc_weight = $_product->get_weight();
								$volume = $this->product_volume($_product, $du);
							}
						}
						$weights[$box] = $acc_weight;
					}
				}
			}

			//make sure no package weight exceed 20kgs or don't calculate the shipping
			foreach ($weights as $weight) {
				$product_bucket[] = array(
				    "PackagingType" => array("Code" => "02"),
				    "Dimensions" => array(
					"UnitOfMeasurement" => array("Code" => strtoupper($du)),
					"Length" => $width,
					"Width" => $length,
					"Height" => $height,
				    ),
				    "PackageWeight" => array(
					"UnitOfMeasurement" => array("Code" => strtoupper(($wu == "kg") ? "kgs" : $wu)),
					"Weight" => $weight
				    )
				);
			}

			return $product_bucket;
		}

		/**
		 * encode order by item
		 */
		function packing_perItem($cart) {
			$product_bucket = array();

			if ($this->measure_unit === "1") {
				$wu = "kg";
				$du = "cm";
			} elseif ($this->measure_unit === "2") {
				$wu = "lbs";
				$du = "in";
			}

			foreach ($cart as $item) {

				$_product = $item['data'];

				if ($_product->exists() && $item['quantity'] > 0 && !$_product->is_virtual()) {

					for ($quantity = 0; $quantity < $item['quantity']; $quantity++) {


						$product_bucket[] = array(
						    "PackagingType" => array("Code" => "02"),
						    "Dimensions" => array(
							"UnitOfMeasurement" => array("Code" => strtoupper($du)),
							"Length" => hzDimNormal($_product->length, $du),
							"Width" => hzDimNormal($_product->width, $du),
							"Height" => hzDimNormal($_product->height, $du),
						    ),
						    "PackageWeight" => array(
							"UnitOfMeasurement" => array("Code" => strtoupper(($wu == "kg") ? "kgs" : $wu)),
							"Weight" => hzWeightNormal($_product->get_weight(), $wu)
						    )
						);
					}
				}
			}

			return $product_bucket;
		}

		/**
		 * Get product volume
		 * @param type $width
		 * @param type $height
		 * @param type $length
		 * @return type 
		 */
		private function product_volume($product, $unit) {
			$width = hzDimNormal($product->width, $unit);
			$height = hzDimNormal($product->height, $unit);
			$length = hzDimNormal($product->length, $unit);
			return $width * $height * $length;
		}

		/**
		 * Admin option for backend update
		 * @global type $woocommerce 
		 */
		function admin_options() {
			global $woocommerce;
			?>
			<h3><?php _e('UPS', 'woothemes'); ?></h3>
			<p><?php _e('Realtime UPS shipping quotes generator.', 'woothemes'); ?></p>
			<table class="form-table">
				<?php
				// Generate the HTML For the settings form.
				$this->generate_settings_html();
				?>
			</table><!--/.form-table-->
			<script type="text/javascript">
				jQuery(window).load(function(){
					$j = jQuery;
					var packing = {
						"express_large": {
							"size": "18\" x 13\" x 3\"",
							"description": "UPS Express Boxes are known for their flexibility and versatility. The large size is suited to ship a wide variety of merchandise. The medium and small sizes are designed to accommodate smaller items such as books and tapes. The boxes are used for UPS Next Day Air, 2nd Day Air, and International services.",
							"shipping_details": "Medium and Small UPS Express Boxes are accepted at UPS Drop Box locations. Large UPS Express Boxes exceed UPS Drop Box size limitations and should be taken to a staffed retail location."
						},
						"express_medium": {
							"size": "15\" x 11\" x 3\"",
							"description": "UPS Express Boxes are known for their flexibility and versatility. The large size is suited to ship a wide variety of merchandise. The medium and small sizes are designed to accommodate smaller items such as books and tapes. The boxes are used for UPS Next Day Air, 2nd Day Air, and International services.",
							"shipping_details": "Medium and Small UPS Express Boxes are accepted at UPS Drop Box locations. Large UPS Express Boxes exceed UPS Drop Box size limitations and should be taken to a staffed retail location."
						},
						"express_small": {
							"size": "13\" x 11\" x 2\"",
							"description": "UPS Express Boxes are known for their flexibility and versatility. The large size is suited to ship a wide variety of merchandise. The medium and small sizes are designed to accommodate smaller items such as books and tapes. The boxes are used for UPS Next Day Air, 2nd Day Air, and International services.",
							"shipping_details": "Medium and Small UPS Express Boxes are accepted at UPS Drop Box locations. Large UPS Express Boxes exceed UPS Drop Box size limitations and should be taken to a staffed retail location.  "
						},
						"express_tube": {
							"size": "38\" x 6\" x 6\"",
							"description": "Ideal for documents which must be rolled rather than folded, such as blueprints, charts, maps, drawings, and posters. ",
							"shipping_details": "This packaging exceeds UPS Drop Box size limitations and should be taken to a staffed retail location. For use with all UPS Next Day Air, 2nd Day Air, and Worldwide services."
						},
						"world_ease_document": {
							"size": "17.5\" x 12.5\" x 3\"",
							"description": "Use this box as the lead packaging for your World Ease shipment. If needed, use the inside of the box for packing slips or other internal documents.",
							"shipping_details": "This item is accepted at all UPS Drop Box locations."
						},
						"10kg_box": {
							"size": "16.5\" x 13.25\" x 10.75\"",
							"description": "Use this box for UPS Worldwide Express shipments. Holds up to 10 kg (22 lbs). Export paperwork included. Charges are based on flat rate and zone.",
							"shipping_details": "This packaging exceeds UPS Drop Box size limitations and should be taken to a staffed retail location. "
						},
						"25kg_box": {
							"size": "19.375\" x 17.375\" x 14\"",
							"description": "Use this box for UPS Worldwide Express® shipments. Holds up to 25 kg (55 lbs). Export paperwork included. Charges are based on flat rate and zone.",
							"shipping_details": "This packaging exceeds UPS Drop Box size limitations and should be taken to a staffed retail location. "
																																																																																																																
						}
																																																																									
					};
																																																											
					var target_option = $j("#woocommerce_ups_packing").val();
					var option = packing[target_option];
					setPackingOption(option, target_option);

					$j("#woocommerce_ups_packing").change(function(){
						var target_option = $j(this).val();
						var option = packing[target_option];
						setPackingOption(option, target_option);
					});
																																																																	
																																																																
					function setPackingOption(option, option_name){
						if(!$j("#ups_packing_option").length){
							var option_row_before = $j("#woocommerce_ups_packing").parents("tr");
							option_row_before.after("<tr id=\"ups_packing_option\"></tr>");
						}
						var option_row = $j("#ups_packing_option");
						if(option_name == "custom_box"){
							option_row.remove();
							$j(".custom_box").parents("tr").show();
						}
						else if(option_name == "shipping_per_item"){
							option_row.remove();
							$j(".custom_box").parents("tr").hide();
						}else{
							$j(".custom_box").parents("tr").hide();
							option_row.html("<td></td><td>Size: "+option["size"]+"<p>"+option["description"]+"</p><p>"+option["shipping_details"]+"</p></td>");
						}
					}
				});
			</script>
			<?php
		}

	}

	/**
	 * Add usps to woo extension pool
	 */
	function add_ups_method($methods) {
		$methods[] = 'WC_Shipping_UPS';
		return $methods;
	}

	add_filter('woocommerce_shipping_methods', 'add_ups_method');
}