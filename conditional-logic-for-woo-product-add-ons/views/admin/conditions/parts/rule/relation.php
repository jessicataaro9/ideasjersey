<?php use MeowCrew\AddonsConditions\Schema;

defined( 'ABSPATH' ) || die;

/**
 * Available vars
 *
 * @var int $loop
 * @var array $current_addon
 * @var array $addons
 * @var FileManager $fileManager
 */

$relations = Schema::getAvailableRelations();

$selected_relation = ! empty( $conditional_rule['relation'] ) ? $conditional_rule['relation'] : false;
?>

<div class="wc-pao-addon-conditions-rule wc-pao-addon-conditions-rule__relation">
	<label for="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>">
		<?php esc_html_e( 'Relation', 'conditional-logic-for-product-addons' ); ?>
		<select name="cfpa_rule_relation_<?php echo esc_attr( $loop ); ?>[]"
				id="wc-pao-addon-conditions-rule-field-<?php echo esc_attr( $loop ); ?>"
				class="wc-pao-addon-conditions-relation">

			<option
					data-value-type="text"
					value="">
				<?php esc_html_e( 'Select Relation', 'conditional-logic-for-product-addons' ); ?>
			</option>

			<?php foreach ( $relations as $relation_key => $relation ) : ?>
				<option
					<?php selected( $selected_relation, $relation_key ); ?>
						data-value-type="<?php echo esc_attr( $relation['value_type'] ); ?>"
						value="<?php echo esc_attr( $relation_key ); ?>">
					<?php echo esc_html( $relation['label'] ); ?>
				</option>
			<?php endforeach; ?>

		</select>
	</label>
</div>
