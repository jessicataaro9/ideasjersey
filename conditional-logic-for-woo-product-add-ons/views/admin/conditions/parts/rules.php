<?php defined( 'ABSPATH' ) || die;

use MeowCrew\AddonsConditions\Core\FileManager;

/**
 * Available vars
 *
 * @var int $loop
 * @var array $current_addon
 * @var array $addons
 * @var FileManager $fileManager
 */

?>
<?php if ( ! empty( $current_addon['conditional_rules'] ) && is_array( $current_addon['conditional_rules'] ) ) : ?>
	<?php foreach ( $current_addon['conditional_rules'] as $conditional_rule ) : ?>

		<?php 
		$fileManager->includeTemplate( 'admin/conditions/parts/rule.php', array(
			'current_addon'    => $current_addon,
			'addons'           => $addons,
			'loop'             => $loop,
			'fileManager'      => $fileManager,
			'conditional_rule' => $conditional_rule,
		) ); 
		?>

	<?php endforeach; ?>
<?php else : ?>
	<?php

	$fileManager->includeTemplate( 'admin/conditions/parts/rule.php', array(
		'current_addon'    => $current_addon,
		'addons'           => $addons,
		'loop'             => $loop,
		'fileManager'      => $fileManager,
		'conditional_rule' => array(
			'field'    => '',
			'relation' => '',
			'value'    => '',
			'type'     => ''
		),
	) ); 
	?>
	<?php 
endif; 
