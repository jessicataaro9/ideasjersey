<?php use MeowCrew\AddonsConditions\Schema;

defined( 'ABSPATH' ) || die;

/**
 * Available vars
 *
 * @var int $loop
 * @var array $current_addon
 * @var array $conditional_rule
 * @var array $addons
 */

$supportedRelations = Schema::getAddonSupportedRelations();
$selectedField      = ! empty( $conditional_rule['field'] ) ? $conditional_rule['field'] : false;

?>

<div class="wc-pao-addon-conditions-rule wc-pao-addon-conditions-rule__field">
	<label for="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>">
		<?php esc_html_e( 'Field', 'conditional-logic-for-product-addons' ); ?>

		<select name="cfpa_rule_field_<?php echo esc_attr( $loop ); ?>[]"
				id="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>"
				class="wc-pao-addon-conditions-field">

			<option value=""
					data-supported-relations="all"
					data-field-type="none">
				<?php esc_html_e( 'Select Field', 'conditional-logic-for-product-addons' ); ?>
			</option>

			<?php foreach ( $addons as $addon ) : ?>

				<?php if ( $current_addon !== $addon ) : ?>

					<?php 
					if ( ! in_array( $addon['type'], Schema::getSupportedConditionsAddonTypes() ) ) {
						continue;
					} 
					?>

					<?php $addonSupportedRelations = implode( ',', $supportedRelations[ $addon['type'] ] ); ?>

					<?php if ( in_array( $addon['type'], array( 'multiple_choice', 'checkbox' ) ) ) : ?>

						<?php if ( ! empty( $addon['options'] ) ) : ?>

							<optgroup label="<?php echo esc_attr( $addon['name'] ); ?>">
								<?php $option_number = 1; ?>
								<?php foreach ( $addon['options'] as $addon_option ) : ?>

									<?php
									$optionValue    = $addon['slug'] . '_' . $option_number;
									$selectedOption = ! empty( $conditional_rule['value'] ) ? $conditional_rule['value'] : '';
									$selectedValue  = $selectedField . '_' . $selectedOption;
									?>

									<option
										<?php selected( $selectedValue, $optionValue ); ?>
											data-supported-relations="<?php echo esc_attr( $addonSupportedRelations ); ?>"
											data-field-type="<?php echo esc_attr( $addon['type'] ); ?>"
											value="<?php echo esc_attr( $optionValue ); ?>">

										<?php 
										echo esc_html( sprintf( '%s %d - %s',
											__( 'Option#', 'conditional-logic-for-product-addons' ),
											$option_number,
											$addon_option['label'] ) )
										?>
									</option>
									<?php $option_number ++; ?>
								<?php endforeach; ?>
							</optgroup>

						<?php endif; ?>
					<?php else : ?>

						<option
							<?php selected( $selectedField, $addon['slug'] ); ?>
								data-supported-relations="<?php echo esc_attr( $addonSupportedRelations ); ?>"
								data-field-type="<?php echo esc_attr( $addon['type'] ); ?>"
								value="<?php echo esc_attr( $addon['slug'] ); ?>">
							<?php echo esc_html( $addon['name'] ); ?>
						</option>

					<?php endif; ?>

				<?php endif; ?>

			<?php endforeach; ?>
		</select>
	</label>
</div>
