<?php
/*
 Plugin Name: Jam
 Plugin URI:  http://onewebcentric.com
 Author: Jonathon McDonald <http://onewebcentric.com>
 Description:  Visit http://onewebcentric.com
 */

/**
 * If this is a product category page, add an action to 
 * change links added to the admin bar
 */
function jm_is_product_cat()
{
	if( is_tax( 'product_cat' ) ) 
		add_action( 'wp_before_admin_bar_render', 'jm_fix_admin_bar' );
}

add_action('wp_head', 'jm_is_product_cat');

/**
 * Will remove the normal edit button, and add one with a proper link
 */
function jm_fix_admin_bar() {
	global $wp_admin_bar, $post;
    $wp_admin_bar->remove_menu('edit');
    $term = get_term_by( 'slug', $_GET['product_cat'], 'product_cat' );
    $wp_admin_bar->add_menu( array( 
    	'parent' => false,
    	'title' => 'Edit Category',
    	'href' => admin_url('edit-tags.php?action=edit&taxonomy=product_cat&tag_ID=' . $term->term_id . '&post_type=product'),
    	'id' => 'edit'
    	)
    );
}