<?php defined( 'ABSPATH' ) || die;

/**
 * Available vars
 *
 * @var int $loop
 * @var array $current_addon
 */

$condition_action = isset( $current_addon['condition_action'] ) ? $current_addon['condition_action'] : false;
$match_type       = isset( $current_addon['condition_match_type'] ) ? $current_addon['condition_match_type'] : false;

?>

<div class="wc-pao-addon-conditions__display-rules">
	<div class="wc-pao-addon-conditions__display-rule">
		<select name="cfpa_rule_action[<?php echo esc_attr( $loop ); ?>]">
			<option <?php selected( $condition_action, 'show' ); ?>
					value="show"><?php esc_html_e( 'Show this field if', 'conditional-logic-for-product-addons' ); ?>
			</option>
			<option <?php selected( $condition_action, 'hide' ); ?>
					value="hide"><?php esc_html_e( 'Hide this field if', 'conditional-logic-for-product-addons' ); ?>
			</option>
		</select>
	</div>
	<div class="wc-pao-addon-conditions__display-rule">
		<select name="cfpa_rule_match_type[<?php echo esc_attr( $loop ); ?>]">
			<option <?php selected( $match_type, 'all' ); ?>
					value="all"><?php esc_html_e( 'All rules match', 'conditional-logic-for-product-addons' ); ?>
			</option>
			<option<?php selected( $match_type, 'any' ); ?>
					value="any"><?php esc_html_e( 'Any rules match', 'conditional-logic-for-product-addons' ); ?>
			</option>
		</select>
	</div>
</div>

