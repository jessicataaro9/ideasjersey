<?php namespace MeowCrew\AddonsConditions\Frontend;

use MeowCrew\AddonsConditions\Core\ServiceContainerTrait;

/**
 * Class Frontend
 *
 * @package MeowCrew\AddonsConditions\Frontend
 */
class Frontend {

	use ServiceContainerTrait;

	public function __construct() {

		new AddToCartManager();

		add_action( 'wp_enqueue_scripts', function () {
			if ( is_product() ) {
				wp_enqueue_script( 'cfpa-frontend.js',
					$this->getContainer()->getFileManager()->locateAsset( 'frontend/frontend.js' ), array( 'jquery' ),
					'1.0.0' );
			}
		} );

		add_action( 'wc_product_addon_start', array( $this, 'extendAddonRendering' ) );
	}

	/**
	 * Render conditional data on the product page
	 *
	 * @param  array  $addon
	 */
	public function extendAddonRendering( $addon ) {
		$addon_type = ! empty( $addon['type'] ) ? $addon['type'] : '';
		$addon_slug = ! empty( $addon['slug'] ) ? $addon['slug'] : '';

		$addonConditionsEnabled     = ! empty( $addon['condition_enabled'] ) ? (bool) $addon['condition_enabled'] : false;
		$addon_condition_action     = ! empty( $addon['condition_action'] ) ? $addon['condition_action'] : '';
		$addon_condition_match_type = ! empty( $addon['condition_match_type'] ) ? $addon['condition_match_type'] : '';
		$addon_product_variations   = ! empty( $addon['product_variations'] ) ? json_encode( $addon['product_variations'] ) : '[]';
		$addon_conditional_rules    = $addonConditionsEnabled && ! empty( $addon['conditional_rules'] ) ? json_encode( $addon['conditional_rules'] ) : '';

		?>
		<div class="wc-pao-addon-condition-data"
		     data-addon-slug="<?php echo esc_attr( $addon_slug ); ?>"
		     data-addon-type="<?php echo esc_attr( $addon_type ); ?>"
		     data-addon-condition-action="<?php echo esc_attr( $addon_condition_action ); ?>"
		     data-addon-condition-match-type="<?php echo esc_attr( $addon_condition_match_type ); ?>"
		     data-addon-product-variations="<?php echo esc_attr( $addon_product_variations ); ?>"
		     data-addon-conditional-rules="<?php echo esc_attr( htmlspecialchars( $addon_conditional_rules ) ); ?>"
		>
		</div>
		<?php
	}
}
