<?php
/**
 * Checks the cart for either an iPad enclosure, or iPad mount
 * and reminds the user they need the other part for a complete
 * set.  If both exist in the cart, it does not display a message.
 *
 * To be clear, these are only helper methods.  This plugin does
 * not use them directly.  Both does_cart_contain_ipad_enclosure 
 * and does_cart_contain_ipad_mount are specific to the project,
 * but does_cart_contain_product_with_category is not and can be used
 * freely with any woocommerce install.  
 *
 * @author Jonathon McDonald <jon@onewebcentric.com>
 */


/**
 * Checks if the users current cart session contains an
 * iPad enclosure.  
 *
 * @return bool True if the cart does contain an iPad enclosure
 */
function cart_contains_ipad_enclosure() 
{
	return does_cart_contain_product_with_category( $category = 'ipad-enclosures' ); 
}

/**
 * Checks if the current cart session contains an iPad mount.
 *
 * @return bool True if the cart does contain an iPad mount
 */
function cart_contains_ipad_mount() 
{
	return does_cart_contain_product_with_category( $category = 'ipad-mounts' ); 
}

/**
 * This function takes a category and sees if a product in the cart
 * actually has that product category.
 *
 * @param string $category Should be the slug of the product category
 * @return bool True if the cart contains any product with this category
 */
function does_cart_contain_product_with_category( $category = '' ) 
{
	// Load woocommerce global to get the cart
	global $woocommerce;
	$cart = $woocommerce->cart;

	// Ensure the cart object exists
	if( !$cart )
	{
		return false;
	}

	// Get contents from the cart
	$cart_contents = $cart->get_cart();

	// If there's no items in the cart, return false
	if( !is_array( $cart_contents ) || sizeof( $cart_contents ) < 1 )
	{
		return false;
	}

	foreach( $cart_contents as $cart_item ) 
	{
		// Construct a product object using the product id
		$product    = new WC_Product( $cart_item['product_id'] );

		// Get all of the product categories this item belongs too
		$terms      = wp_get_post_terms( $product->get_post_data()->ID, 'product_cat' );
		$categories = array();

		// Add all the categories to an array
		foreach( $terms as $term ) {
			$categories[] = $term->slug;
		}

		// If the array contains the category passed, return true
		if( in_array($category, $categories) ) {
			return true;
		}
	}

	// No product was found
	return false;
}