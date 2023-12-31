<?php
/**
 * Class: Jet_Woo_Builder_Cart_Document
 * Name: Cart Template
 * Slug: jet-woo-builder-cart
 */

use Elementor\Controls_Manager;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Jet_Woo_Builder_Cart_Document extends Jet_Woo_Builder_Document_Base {

	public function get_name() {
		return 'jet-woo-builder-cart';
	}

	public static function get_title() {
		return esc_html__( 'Jet Woo Cart Template', 'jet-woo-builder' );
	}

	public static function get_properties() {

		$properties = parent::get_properties();

		$properties['woo_builder_template_settings'] = true;

		return $properties;

	}

}