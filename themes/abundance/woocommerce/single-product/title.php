<?php
/**
 * Single Product Title
 */
?>
<?php global $product, $post; ?>
<div style="float:left; width: 70%;">
<?php if( $product->get_sku() != '' ): ?>
	<?php echo '<h3 class="cufon_headings">' . $product->get_sku() . '</h3>' ?>
<?php else: ?>
	<?php echo '<h3 class="cufon_headings">' . the_title() . '</h3>'; ?>
<?php endif; ?>