<?php

defined( 'ABSPATH' ) || die;
use  MeowCrew\AddonsConditions\Core\FileManager ;
/**
 * Available variables
 *
 * @var int $loop
 * @var array $current_addon
 * @var array $addons
 * @var FileManager $fileManager
 * @var WC_Product_Variable $product
 */
$addons['product_variations'] = ( isset( $current_addon['product_variations'] ) ? (array) $current_addon['product_variations'] : array() );
$is_disabled = ( clfwpao_fs()->is_premium() ? false : true );
?>

<div class="wc-pao-addon-conditions__variations-block">
	<hr style="margin: 20px 0">

	<div>
		<h4><?php 
esc_html_e( 'Variations', 'conditional-logic-for-product-addons' );
?></h4>
		<p style="padding: 0">
			<?php 
esc_html_e( 'If you want to show this field only for certain variations, chose the desired variations
			below. Leave empty to don\'t add any restrictions by variations.', 'conditional-logic-for-product-addons' );
?>
		</p>
		<select multiple name="cfpa_rule_product_variations[<?php 
echo  esc_attr( $loop ) ;
?>][]"
		        class="wc-enhanced-select"
			<?php 
disabled( !clfwpao_fs()->is_premium() );
?>
			    style="float:none; width: 100%">
			<?php 
?>
		</select>
		<?php 

if ( !clfwpao_fs()->is_premium() ) {
    ?>
			<p style="color: red; padding: 0">
				<?php 
    _e( 'Available only in the premium version.', 'conditional-logic-for-product-addons' );
    ?>
				<a target="_blank"
				   href="<?php 
    echo  esc_attr( clfwpao_fs()->get_upgrade_url() ) ;
    ?>"><?php 
    esc_html_e( 'Upgrade', 'conditional-logic-for-product-addons' );
    ?></a>
			</p>
		<?php 
}

?>
	</div>
	<div class="clear"></div>
</div>
<br>
