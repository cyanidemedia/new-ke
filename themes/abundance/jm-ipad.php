<?php
/**
 * Helps make iPad enclosure purchases represent more or less a two step process
 *
 * @author Jonathon McDonald <jon@onecentric.com>
 */

function jm_add_to_cart($message) 
{
	global $woocommerce, $product; 

	$message .= " Now choose your rack mount.";

	return $message;
}

function jm_add_to_cart_listener( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data )
{
	$product = new WC_Product( $product_id );

	$terms = wp_get_post_terms( $product->get_post_data()->ID, 'product_cat' );

	foreach( $terms as $term ) {
		$categories[] = $term->slug;
	}

	if( in_array('ipad-enclosures', $categories) ) {
		add_filter('woocommerce_add_to_cart_message', 'jm_add_to_cart', 999);
	}
}

add_action('woocommerce_add_to_cart', 'jm_add_to_cart_listener', 10, 6);