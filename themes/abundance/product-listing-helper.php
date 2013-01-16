<?php

function add_sku() {
	global $product;
	echo $product->get_sku() . '<br />';
	return;
}

add_action('woocommerce_after_shop_loop_item_title', 'add_sku', 0, 1);