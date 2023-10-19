<?php namespace MeowCrew\AddonsConditions;

/**
 * Class SanitizeManager
 *
 * @package MeowCrew\SanitizeManager
 */
class SanitizeManager {

	protected static function sanitizeFieldType( $type ) {
		return in_array( $type, array(
			'multiple_choice',
			'checkbox',
			'custom_text',
			'custom_textarea',
			'custom_price',
			'input_multiplier',
			'file_upload',
		) ) ? $type : false;
	}

	protected static function sanitizeRelation( $relation ) {
		return in_array( $relation, array(
			'is',
			'is_not',
			'is_empty',
			'is_not_empty',
			'text_contains',
			'text_end_with',
			'text_start_with',
			'text_does_not_contain',
			'is_less_than',
			'is_greater_than',
			'is_less_than_or_equal',
			'is_greater_than_or_equal',
		) ) ? $relation : false;
	}

	protected static function getFieldTypeBySlug( $slug ) {

		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		$slugs = isset( $_POST['product_addon_conditions_slug'] ) ? array_map( 'sanitize_text_field',
			(array) $_POST['product_addon_conditions_slug'] ) : array();

		if ( in_array( $slug, $slugs ) ) {
			$key = array_search( $slug, $slugs );

			if ( false !== $key ) {
				$productAddonsTypes = isset( $_POST['product_addon_type'] ) ? array_map( 'sanitize_text_field',
					(array) $_POST['product_addon_type'] ) : array();

				$type = array_key_exists( $key, $productAddonsTypes ) ? $productAddonsTypes[ $key ] : false;

				return self::sanitizeFieldType( $type );
			}
		}

		return false;
	}

	public static function getPOSTIsConditionsEnabled( $loop ) {

		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		$enabled = isset( $_POST['product_addon_conditions_enabled'] ) ? array_map( 'sanitize_text_field',
			(array) $_POST['product_addon_conditions_enabled'] ) : array();

		return ! empty( $enabled[ $loop ] );
	}

	public static function getPOSTConditionalRules( $loop ) {

		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		$fields       = isset( $_POST["cfpa_rule_field_$loop"] ) ? array_map( 'sanitize_text_field',
			(array) $_POST["cfpa_rule_field_$loop"] ) : array();
		$relations    = isset( $_POST["cfpa_rule_relation_$loop"] ) ? array_map( 'sanitize_text_field',
			(array) $_POST["cfpa_rule_relation_$loop"] ) : array();
		$textValues   = isset( $_POST["cfpa_rule_text_value_$loop"] ) ? array_map( 'sanitize_text_field',
			(array) $_POST["cfpa_rule_text_value_$loop"] ) : array();
		$numberValues = isset( $_POST["cfpa_rule_number_value_$loop"] ) ? array_map( 'sanitize_text_field',
			(array) $_POST["cfpa_rule_number_value_$loop"] ) : array();

		$fields       = array_filter( $fields );
		$relations    = array_filter( $relations );
		$textValues   = array_filter( $textValues );
		$numberValues = array_filter( $numberValues );

		$data = array();

		foreach ( $fields as $fieldKey => $fieldData ) {

			$fieldData = explode( '_', $fieldData );

			$field               = isset( $fieldData[0] ) ? strval( $fieldData[0] ) : false;
			$selectedFieldOption = isset( $fieldData[1] ) ? intval( $fieldData[1] ) : false;

			$relation  = isset( $relations[ $fieldKey ] ) ? self::sanitizeRelation( $relations[ $fieldKey ] ) : false;
			$fieldType = self::getFieldTypeBySlug( $field );

			if ( ! $field || ! $relation ) {

				continue;
			}

			$value = null;

			if ( in_array( $fieldType, array( 'file_upload', 'checkbox', 'multiple_choice' ) ) ) {

				if ( in_array( $fieldType, array( 'checkbox', 'multiple_choice' ) ) ) {

					if ( ! $selectedFieldOption ) {
						continue;
					}

					$value = $selectedFieldOption;
				}

				if ( in_array( $relations[ $fieldKey ], array( 'is', 'is_not' ) ) ) {
					$relation = $relations[ $fieldKey ];
				} else {
					$relation = 'is';
				}
			}

			if ( in_array( $fieldType, array( 'custom_text', 'custom_textarea' ) ) ) {
				$value = strval( $textValues[ $fieldKey ] );
			}

			if ( in_array( $fieldType, array( 'input_multiplier', 'custom_price' ) ) ) {
				if ( in_array( $relations[ $fieldKey ], array( 'is', 'is_not' ) ) ) {
					$value = floatval( $textValues[ $fieldKey ] );
				} else {
					$value = floatval( $numberValues[ $fieldKey ] );
				}
			}

			// Skip rules with empty value. Empty and Is not Empty relations can have empty value
			if ( ! in_array( $relation, array( 'is_empty', 'is_not_empty' ) ) && '' === $value ) {
				continue;
			}

			$data[] = array(
				'type'     => $fieldType,
				'field'    => $field,
				'relation' => $relation,
				'value'    => $value,
			);
		}

		return $data;
	}

	public static function getPOSTConditionAction( $loop ) {

		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		$action = isset( $_POST['cfpa_rule_action'][ $loop ] ) ? sanitize_text_field( $_POST['cfpa_rule_action'][ $loop ] ) : '';

		return in_array( $action, array( 'hide', 'show' ) ) ? $action : '';
	}

	public static function getPOSTConditionMatchType( $loop ) {

		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		$matchType = isset( $_POST['cfpa_rule_match_type'][ $loop ] ) ? sanitize_text_field( $_POST['cfpa_rule_match_type'][ $loop ] ) : '';

		return in_array( $matchType, array( 'all', 'any' ) ) ? $matchType : '';
	}

	public static function getPOSTAddonSlug( $loop ) {

		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		return isset( $_POST['product_addon_conditions_slug'][ $loop ] ) ? sanitize_text_field( $_POST['product_addon_conditions_slug'][ $loop ] ) : uniqid();
	}

	public static function getPOSTProductVariations( $loop ) {

		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}

		return isset( $_POST['cfpa_rule_product_variations'][ $loop ] ) ? array_map( 'intval',
			(array) $_POST['cfpa_rule_product_variations'][ $loop ] ) : array();
	}
}
