<?php
/**
 * Helps make iPad enclosure purchases represent more or less a two step process
 *
 * @author Jonathon McDonald <jon@onecentric.com>
 */

/**
 * Appends a message to the cart that the user should remember
 * to pick up a rack mount if purchasing a ipad enclosure
 *
 * @return String The modified cart message
 */
function jm_add_to_cart_ipad_enclosure($message) 
{
	global $woocommerce, $product; 

	$message .= " Now choose your mount.";

	return $message;
}

/**
 * Appends a message to the cart that the user should remember
 * to pick up an enclosure if purchasing a ipad mount
 *
 * @return String The modified cart message
 */
function jm_add_to_cart_ipad_mount($message) 
{
	global $woocommerce, $product; 

	$message .= " Now choose your enclosure.";

	return $message;
}


/**
 * Listens to see if an item is added to the cart.  If it is,
 * this will check to see if the item is a ipad enclosure.  
 * If the item is an ipad enclosure it will then add a filter
 * telling the customer to pick up an ipad enclosure
 *
 * @param id Product ID used to instantiate a product
 */
function jm_add_to_cart_listener( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data )
{
	// Fetch a product object using the product id
	$product = new WC_Product( $product_id );

	// Get all of the product categories this item belongs too
	$terms = wp_get_post_terms( $product->get_post_data()->ID, 'product_cat' );

	// Add all the categories to an array
	foreach( $terms as $term ) {
		$categories[] = $term->slug;
	}

	// If the array contains ipad-enclosures we know this was indeed a mount
	if( in_array('ipad-enclosures', $categories) ) {
		add_filter('woocommerce_add_to_cart_message', 'jm_add_to_cart_ipad_enclosure', 999);
	}

		// If the array contains ipad-enclosures we know this was indeed a enclosure
	if( in_array('ipad-mounts', $categories) ) {
		add_filter('woocommerce_add_to_cart_message', 'jm_add_to_cart_ipad_mount', 999);
	}
}

add_action('woocommerce_add_to_cart', 'jm_add_to_cart_listener', 10, 6);