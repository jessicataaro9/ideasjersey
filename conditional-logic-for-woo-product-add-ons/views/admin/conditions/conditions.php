<?php defined( 'ABSPATH' ) || die;

use MeowCrew\AddonsConditions\Core\FileManager;
use MeowCrew\AddonsConditions\Core\ServiceContainer;

/**
 * Available vars
 *
 * @var int $loop
 * @var int $post_id
 * @var array $current_addon
 * @var array $addons
 * @var bool $new_addon
 * @var bool $is_product
 * @var FileManager $fileManager
 */

$fileManager = ServiceContainer::getInstance()->getFileManager();

$slug = isset( $current_addon['slug'] ) ? $current_addon['slug'] : '';

if ( ! empty( $current_addon['slug'] ) ) {
	$slug    = $current_addon['slug'];
	$hasSlug = true;
} else {
	$slug    = uniqid();
	$hasSlug = false;
}

$addon_enabled = isset( $current_addon['condition_enabled'] ) ? $current_addon['condition_enabled'] : false;
?>

<div class="wc-pao-addons-secondary-settings">
	<div class="wc-pao-row wc-pao-row-conditions-settings">
		<label for="wc-pao-addon-conditions-enabled-<?php echo esc_attr( $loop ); ?>">

			<input type="hidden" value="<?php echo esc_attr( $slug ); ?>"
			       name="product_addon_conditions_slug[<?php echo esc_attr( $loop ); ?>]">

			<input type="checkbox" id="wc-pao-addon-conditions-enabled-<?php echo esc_attr( $loop ); ?>"
			       class="wc-pao-addon-conditions-enabled"
			       name="product_addon_conditions_enabled[<?php echo esc_attr( $loop ); ?>]" <?php checked( $addon_enabled ); ?> />

			<?php esc_html_e( 'Conditional logic', 'conditional-logic-for-product-addons' ); ?>
		</label>

		<div class="wc-pao-addon-conditions"
		     id="wc-pao-addon-conditions-<?php echo esc_attr( $loop ); ?>" <?php echo ! $addon_enabled ? 'style="display:none"' : ''; ?>>
			<?php if ( ! clfwpao_fs()->is_premium() && ( ! $new_addon && $hasSlug ) ): ?>
				<div class="cfpa-notice" style="margin-bottom: 20px; border-left-color: red;">
					<p style="font-size: 13px">
						<?php esc_html_e( 'Please note that the free version allows you to set only one conditional rule.',
							'conditional-logic-for-product-addons' ); ?>
						<a target="_blank"
						   href="<?php echo esc_attr( clfwpao_fs()->get_upgrade_url() ) ?>"><?php esc_html_e( 'Upgrade to get unlimited conditions',
								'conditional-logic-for-product-addons' ); ?></a>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( ! $new_addon && $hasSlug ) : ?>
				<?php
				$fileManager->includeTemplate( 'admin/conditions/parts/display-rules.php', array(
					'current_addon' => $current_addon,
					'loop'          => $loop,
				) );
				?>

				<div class="wc-pao-addon-conditions__rules">
					<?php
					$fileManager->includeTemplate( 'admin/conditions/parts/rules.php', array(
						'current_addon' => $current_addon,
						'addons'        => $addons,
						'loop'          => $loop,
						'fileManager'   => $fileManager,
					) );
					?>
				</div>

				<?php
				$fileManager->includeTemplate( 'admin/conditions/parts/rule/add-new.php', array(
					'current_addon' => $current_addon,
					'addons'        => $addons,
					'loop'          => $loop,
					'fileManager'   => $fileManager,
				) );
				?>
			<?php else : ?>
				<div class="cfpa-notice">
					<p style="font-size: 13px">
						<?php esc_html_e( 'Please update (re-save) the post to set up conditional logic.',
							'conditional-logic-for-product-addons' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php
			if ( $is_product ) {

				$product = wc_get_product( $post_id );

				if ( $product && method_exists( $product, 'get_available_variations' ) ) {

					$fileManager->includeTemplate( 'admin/conditions/parts/variations-rules.php', array(
						'current_addon' => $current_addon,
						'addons'        => $addons,
						'loop'          => $loop,
						'fileManager'   => $fileManager,
						'product'       => $product,
					) );
				}
			}
			?>
		</div>
	</div>
</div>
