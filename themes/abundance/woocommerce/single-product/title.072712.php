<?php
/**
 * Single Product Title
 */
?>
<?php global $product, $post; ?>
<h1 itemprop="name" class="product_title entry-title"><?php the_title(); ?></h1>
<?php echo $product->get_sku(); ?>