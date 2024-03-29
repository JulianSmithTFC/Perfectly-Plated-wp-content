<?php
/**
 * Product Bundle add-to-cart buttons wrapper template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/add-to-cart/bundle-add-to-cart.php'.
 *
 * On occasion, this template file may need to be updated and you (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the readme will list any important changes.
 *
 * @version 5.9.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="cart bundle_data bundle_data_<?php echo $product_id; ?>" data-bundle_price_data="<?php echo esc_attr( json_encode( $bundle_price_data ) ); ?>" data-bundle_id="<?php echo $product_id; ?>"><?php

	if ( $product->is_purchasable() ) {

		/**
		 * 'woocommerce_before_add_to_cart_button' action.
		 */
		do_action( 'woocommerce_before_add_to_cart_button' );

		?><div class="bundle_wrap">
			<div class="bundle_price"></div>
			<div class="bundle_error" style="display:none">
				<div class="woocommerce-info">
					<ul class="msg"></ul>
				</div>
			</div>
			<div class="bundle_availability"><?php

				// Availability html.
				echo $availability_html;

			?></div>
			<div class="bundle_button"><?php

				/**
				 * woocommerce_bundles_add_to_cart_button hook.
				 *
				 * @hooked wc_pb_template_add_to_cart_button - 10
				 */
				do_action( 'woocommerce_bundles_add_to_cart_button', $product );

			?></div>
			<input type="hidden" name="add-to-cart" value="<?php echo $product_id; ?>" />
		</div><?php

		/** WC Core action. */
		do_action( 'woocommerce_after_add_to_cart_button' );

	} else {

		?><div class="bundle_unavailable woocommerce-info"><?php
			echo __( 'This product is currently unavailable.', 'woocommerce-product-bundles' );
		?></div><?php
	}

?></div>
