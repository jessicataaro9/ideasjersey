<?php

defined( 'ABSPATH' ) || die;
use  MeowCrew\AddonsConditions\Core\FileManager ;
/**
 * Available vars
 *
 * @var int $loop
 * @var array $current_addon
 * @var array $addons
 * @var FileManager $fileManager
 */
$isFree = true;
?>
<div class="wc-pao-addon-conditions-rules-template hidden">
	<?php 
$fileManager->includeTemplate( 'admin/conditions/parts/rule.php', array(
    'current_addon'    => $current_addon,
    'conditional_rule' => array(
    'field'    => '',
    'relation' => '',
    'value'    => '',
    'type'     => '',
),
    'addons'           => $addons,
    'loop'             => $loop,
    'fileManager'      => $fileManager,
) );
?>
</div>

<div class="wc-pao-addon-conditions__add-new">
	<?php 

if ( !clfwpao_fs()->is_premium() ) {
    ?>
        <button disabled class="button"
                title="<?php 
    esc_html_e( 'Upgrade to unlock this feature', 'conditional-logic-for-product-addons' );
    ?>">
			<?php 
    esc_html_e( 'Add Condition', 'conditional-logic-for-product-addons' );
    ?>
        </button>
	<?php 
}

?>

	<?php 
?>
</div>


