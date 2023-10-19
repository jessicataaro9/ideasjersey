<?php defined( 'ABSPATH' ) || die;

/**
 * Available vars
 *
 * @var int $loop
 * @var array $conditional_rule
 */
?>

<div class="wc-pao-addon-conditions-rule wc-pao-addon-conditions-rule__value">
	<div class="wc-pao-addon-conditions-rule__value-inner" data-value-input-type="text">
		<label for="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>">
			<?php esc_html_e( 'Value', 'conditional-logic-for-product-addons' ); ?>
			<input id="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>"
				   type="text"
				   value="<?php echo esc_attr( $conditional_rule['value'] ); ?>"
				   name="cfpa_rule_text_value_<?php echo esc_attr( $loop ); ?>[]">
		</label>
	</div>

	<div class="wc-pao-addon-conditions-rule__value-inner" data-value-input-type="number">
		<label for="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>">
			<?php esc_html_e( 'Value', 'conditional-logic-for-product-addons' ); ?>
			<input id="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>"
				   type="number"
				   step="any"
				   style="width: 100%"
				   value="<?php echo esc_attr( $conditional_rule['value'] ); ?>"
				   name="cfpa_rule_number_value_<?php echo esc_attr( $loop ); ?>[]">
		</label>
	</div>

	<div class="wc-pao-addon-conditions-rule__value-inner" data-value-input-type="checkbox">
		<label for="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>">
			<?php esc_html_e( 'Value', 'conditional-logic-for-product-addons' ); ?>
			<input
					type="text"
					placeholder="<?php echo esc_attr( 'Checked', 'conditional-logic-for-product-addons' ); ?>"
					id="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>"
					readonly>
		</label>
	</div>

	<div class="wc-pao-addon-conditions-rule__value-inner" data-value-input-type="file">
		<label for="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>">
			<?php esc_html_e( 'Value', 'conditional-logic-for-product-addons' ); ?>
			<input
					type="text"
					placeholder="<?php echo esc_attr( 'Selected', 'conditional-logic-for-product-addons' ); ?>"
					id="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>"
					readonly>
		</label>
	</div>

	<div class="wc-pao-addon-conditions-rule__value-inner" data-value-type="none">

	</div>

</div>
