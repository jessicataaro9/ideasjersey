<?php
/**
 * Kava Theme Integration
 */

// Single product wrapper
add_action( 'jet-woo-builder/blank-page/before-content', 'jet_woo_kava_open_site_main_wrap', -999 );
add_action( 'jet-woo-builder/full-width-page/before-content', 'jet_woo_kava_open_site_main_wrap', -999 );
add_action( 'jet-woo-builder/blank-page/after-content', 'jet_woo_kava_close_site_main_wrap', 999 );
add_action( 'jet-woo-builder/full-width-page/after-content', 'jet_woo_kava_close_site_main_wrap', 999 );

// WooCommerce hooks fix
if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
	add_action( 'elementor/widgets/register', 'jet_woo_kava_fix_wc_hooks' );
} else {
	add_action( 'elementor/widgets/widgets_registered', 'jet_woo_kava_fix_wc_hooks' );
}

// Enqueue styles
add_action( 'wp_enqueue_scripts', 'jet_woo_kava_enqueue_styles' );

if ( ! current_theme_supports('woocommerce') ) {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}

/**
 * Fix WooCommerce hooks for Kava theme
 *
 * @return void
 */
function jet_woo_kava_fix_wc_hooks() {

	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 10 );

	remove_filter( 'woocommerce_cross_sells_columns', 'kava_woocommerce_change_cross_sells_columns_count' );

}

/**
 * Open .site-main wrapper for products
 *
 * @return void
 */
function jet_woo_kava_open_site_main_wrap() {
	if ( ! is_singular( [ jet_woo_builder_post_type()->slug(), 'product' ] ) ) {
		return;
	}

	echo '<div class="site-main">';
}

/**
 * Close .site-main wrapper for products
 *
 * @return void
 */
function jet_woo_kava_close_site_main_wrap() {
	if ( ! is_singular( [ jet_woo_builder_post_type()->slug(), 'product' ] ) ) {
		return;
	}

	echo '</div>';
}

/**
 * Enqueue Kava theme integration stylesheets.
 *
 * @return void
 * @since  1.0.0
 * @access public
 */
function jet_woo_kava_enqueue_styles() {
	wp_enqueue_style(
		'jet-woo-builder-kava',
		jet_woo_builder()->plugin_url( 'includes/compatibility/packages/themes/kava/assets/css/style.css' ),
		false,
		jet_woo_builder()->get_version()
	);
}