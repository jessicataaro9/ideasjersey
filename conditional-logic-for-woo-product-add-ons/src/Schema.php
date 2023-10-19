<?php namespace MeowCrew\AddonsConditions;

/**
 * Class Schema
 *
 * @package MeowCrew\Schema
 */
class Schema {

	public static function getSupportedAddonTypes() {
		/**
		 * Addon types that support conditions
		 *
		 * @since 1.0.0
		 **/
		return apply_filters( 'product_addons_conditions/schema/supported_addon_types', array(
			'multiple_choice',
			'checkbox',
			'custom_text',
			'custom_textarea',
			'custom_price',
			'input_multiplier',
			'file_upload',
			'heading'
		) );
	}

	public static function getSupportedConditionsAddonTypes() {
		/**
		 * Addon types that can be used in conditions
		 *
		 * @since 1.0.0
		 **/
		return apply_filters( 'product_addons_conditions/schema/supported_conditions_addon_types', array(
			'multiple_choice',
			'checkbox',
			'custom_text',
			'custom_textarea',
			'custom_price',
			'input_multiplier',
			'file_upload',
		) );
	}

	public static function getAddonSupportedRelations() {
		/**
		 * Addons relations
		 *
		 * @since 1.0.0
		 **/
		return apply_filters( 'product_addons_conditions/schema/addon_supported_relations', array(
			'multiple_choice'  => array( 'is', 'is_not' ),
			'checkbox'         => array( 'is', 'is_not' ),
			'custom_text'      => array(
				'is',
				'is_not',
				'is_empty',
				'is_not_empty',
				'text_contains',
				'text_end_with',
				'text_start_with',
				'text_does_not_contain'
			),
			'custom_textarea'  => array(
				'is',
				'is_not',
				'is_empty',
				'is_not_empty',
				'text_contains',
				'text_end_with',
				'text_start_with',
				'text_does_not_contain'
			),
			'custom_price'     => array(
				'is',
				'is_not',
				'is_less_than',
				'is_greater_than',
				'is_less_than_or_equal',
				'is_greater_than_or_equal'
			),
			'input_multiplier' => array(
				'is',
				'is_not',
				'is_less_than',
				'is_greater_than',
				'is_less_than_or_equal',
				'is_greater_than_or_equal'
			),
			'file_upload'      => array( 'is', 'is_not' ),
			'heading'          => array(),
		) );
	}

	public static function getAvailableRelations() {
		/**
		 * List of available relations
		 *
		 * @since 1.0.0
		 **/
		return apply_filters( 'product_addons_conditions/schema/available_relations', array(
			'is' => array(
				'label'      => __( 'Is', 'conditional-logic-for-product-addons' ),
				'value_type' => 'text',
			),

			'is_not' => array(
				'label'      => __( 'Is not', 'conditional-logic-for-product-addons' ),
				'value_type' => 'text',
			),

			'is_empty' => array(
				'label'      => __( 'Is empty', 'conditional-logic-for-product-addons' ),
				'value_type' => 'none',
			),

			'is_not_empty' => array(
				'label'      => __( 'Is not empty', 'conditional-logic-for-product-addons' ),
				'value_type' => 'none',
			),

			'is_greater_than' => array(
				'label'      => __( 'Is greater than', 'conditional-logic-for-product-addons' ),
				'value_type' => 'number',
			),

			'is_less_than' => array(
				'label'      => __( 'Is less than', 'conditional-logic-for-product-addons' ),
				'value_type' => 'number',
			),

			'is_greater_than_or_equal' => array(
				'label'      => __( 'Is greater than or equal', 'conditional-logic-for-product-addons' ),
				'value_type' => 'number',
			),

			'is_less_than_or_equal' => array(
				'label'      => __( 'Is less than or equal', 'conditional-logic-for-product-addons' ),
				'value_type' => 'number',
			),

			'text_contains' => array(
				'label'      => __( 'Text contains', 'conditional-logic-for-product-addons' ),
				'value_type' => 'text',
			),

			'text_does_not_contain' => array(
				'label'      => __( 'Text does not contain', 'conditional-logic-for-product-addons' ),
				'value_type' => 'text',
			),

			'text_start_with' => array(
				'label'      => __( 'Text starts with', 'conditional-logic-for-product-addons' ),
				'value_type' => 'text',
			),

			'text_end_with' => array(
				'label'      => __( 'Text end with', 'conditional-logic-for-product-addons' ),
				'value_type' => 'text',
			),
		) );
	}
}
