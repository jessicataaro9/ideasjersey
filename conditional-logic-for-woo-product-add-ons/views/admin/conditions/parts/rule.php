<?php defined( 'ABSPATH' ) || die;

use MeowCrew\AddonsConditions\Core\FileManager;

/**
 * Available vars
 *
 * @var int $loop
 * @var array $current_addon
 * @var array $conditional_rule
 * @var array $addons
 * @var FileManager $fileManager
 */

?>
<div class="wc-pao-addon-conditions__rule-row">
	<?php
	$fileManager->includeTemplate( 'admin/conditions/parts/rule/field.php', array(
		'current_addon'    => $current_addon,
		'addons'           => $addons,
		'loop'             => $loop,
		'conditional_rule' => $conditional_rule,
	) );
	?>

	<?php
	$fileManager->includeTemplate( 'admin/conditions/parts/rule/relation.php', array(
		'current_addon'    => $current_addon,
		'addons'           => $addons,
		'loop'             => $loop,
		'conditional_rule' => $conditional_rule,
	) );
	?>

	<?php
	$fileManager->includeTemplate( 'admin/conditions/parts/rule/value.php', array(
		'current_addon'    => $current_addon,
		'addons'           => $addons,
		'loop'             => $loop,
		'conditional_rule' => $conditional_rule,
	) );
	?>

	<?php
	$fileManager->includeTemplate( 'admin/conditions/parts/rule/remove.php', array(
		'current_addon'    => $current_addon,
		'addons'           => $addons,
		'loop'             => $loop,
		'conditional_rule' => $conditional_rule,
	) );
	?>
</div>
