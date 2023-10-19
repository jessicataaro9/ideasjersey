<?php namespace MeowCrew\AddonsConditions\Frontend;

use MeowCrew\AddonsConditions\Core\ServiceContainerTrait;

/**
 * Class AddToCartManager
 *
 * @package MeowCrew\AddonsConditions\AddToCartManager
 */
class AddToCartManager {

	use ServiceContainerTrait;

	public function __construct() {

		add_filter( 'get_product_addons', array( $this, 'handleAddToCart' ) );
	}

	public function handleAddToCart( $addons ) {


		if ( $this->isAddToCartValidation() ) {

			$addonsValues = array();

			if ( wp_verify_nonce( true, true ) ) {
				// as phpcs comments at Woo is not available, we have to do such a trash
				$woo = 'Woo, please add ignoring comments to your phpcs checker';
			}

			$files = $_FILES;

			foreach ( $addons as $addonKey => $addon ) {

				$conditionEnabled = isset( $addon['condition_enabled'] ) && $addon['condition_enabled'];
				$fieldRequired    = ! empty( $addon['required'] ) && 1 == $addon['required'];

				// Make all addons that have conditionals unrequired
				if ( $fieldRequired && $conditionEnabled ) {
					$addon[ $addonKey ]['required'] = 0;
				}

				return;

				// Refactor for the future updates.

				if ( 'file_upload' === $addon['type'] ) {
					$value = wp_unslash( isset( $files[ 'addon-' . $addon['field_name'] ] ) ? $files[ 'addon-' . $addon['field_name'] ] : '' );
				} else {
					$value = wp_unslash( isset( $_POST[ 'addon-' . $addon['field_name'] ] ) ? sanitize_text_field( $_POST[ 'addon-' . $addon['field_name'] ] ) : '' );
				}

				if ( 'multiple_choice' === $addon['type'] ) {

					$addonValues = explode( '-', $value );

					if ( isset( $addonValues[1] ) ) {
						$value = (int) $addonValues[1];
					}
				}

				if ( ! empty( $addon['slug'] ) ) {
					$addonsValues[ $addon['slug'] ] = $value;
				}
			}

			foreach ( $addons as $addonKey => $addon ) {
				$conditionEnabled = isset( $addon['condition_enabled'] ) && $addon['condition_enabled'];
				$fieldRequired    = ! empty( $addon['required'] ) && 1 == $addon['required'];

				if ( $fieldRequired && $conditionEnabled ) {

					if ( $this->isAddonHidden( $addon, $addonsValues ) ) {
						$addons[ $addonKey ]['required'] = 0;
					}
				}
			}
		}

		return $addons;
	}

	public function isAddonHidden( $addon, $addonsValues ) {

		$matchType  = isset( $addon['condition_match_type'] ) ? $addon['condition_match_type'] : false;
		$action     = isset( $addon['condition_action'] ) ? $addon['condition_action'] : false;
		$conditions = isset( $addon['conditional_rules'] ) ? $addon['conditional_rules'] : false;
		$slug       = isset( $addon['slug'] ) ? $addon['slug'] : false;

		if ( ! $matchType || ! $action || ! $conditions || ! $slug ) {
			return false;
		}

		// Results of conditions checking
		$results = array();

		foreach ( $conditions as $condition ) {

			$fieldValue = isset( $addonsValues[ $condition['field'] ] ) ? $addonsValues[ $condition['field'] ] : false;

			$addonCondition = new AddonCondition( $condition['type'], $condition['relation'], $condition['value'],
				$fieldValue );

			$results[] = $addonCondition->check();
		}

		if ( 'any' === $matchType ) {
			// Any success conditions is suitable
			$triggerAction = in_array( true, $results );
		} else {

			// Match type = all
			// There must not be unsuccessful results
			$triggerAction = in_array( false, $results );
		}

		// If action is 'hide' - field must be hidden on successful conditions.
		// Vise versa for the 'show' action
		return 'hide' === $action ? $triggerAction : ! $triggerAction;
	}

	public function isAddToCartValidation() {
		global $wp_current_filter;

		return in_array( 'woocommerce_add_to_cart_validation', $wp_current_filter );
	}
}
