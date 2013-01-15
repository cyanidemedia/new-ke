<?php
/**
 * Simple Product Add to Cart
 */
 
global $woocommerce, $product;

if( $product->get_price() === '') return;
?>

<?php 
	// Availability
	$availability = $product->get_availability();
	
	if ($availability['availability']) :
		echo apply_filters( 'woocommerce_stock_html', '<p class="stock '.$availability['class'].'">'.$availability['availability'].'</p>', $availability['availability'] );
    endif;
?>

<?php if (!$product->is_in_stock()) : ?>
	<link itemprop="availability" href="http://schema.org/OutOfStock">
<?php else : ?>

	<link itemprop="availability" href="http://schema.org/InStock">

	<?php do_action('woocommerce_before_add_to_cart_form'); ?>
	
	<form action="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="cart" method="post" enctype='multipart/form-data'>
	 	<?php do_action('woocommerce_before_add_to_cart_button'); ?>
	 	<div class="warranty">
	 		<?php 
	 		if ( ! ( get_option('woocommerce_limit_downloadable_product_qty')=='yes' && $product->is_downloadable() && $product->is_virtual() ) ) 
	 			?> <input name="quantity" type="hidden" value="1" /> <?php
	 		?>
	 		<button type="submit" class="button alt"><?php echo apply_filters('single_add_to_cart_text', __('Add to cart', 'woocommerce'), $product->product_type); ?></button>
			<a href="?page_id=869" target="_blank"><img src="../wp-content/uploads/2012/04/lifetime-warranty.png" class="warPic" />* click for warranty details</a>
		</div>
		<?php do_action('woocommerce_after_add_to_cart_button'); ?>
	</form>
	
	<?php do_action('woocommerce_after_add_to_cart_form'); ?>
	<?php do_action('jm_show_accessories'); ?>
<?php endif; ?>