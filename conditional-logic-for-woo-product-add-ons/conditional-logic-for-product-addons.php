<?php if ( ! defined( 'WPINC' ) ) {
	die;
}

use MeowCrew\AddonsConditions\AddonsConditionsPlugin;

/**
 *
 * Plugin Name:       Conditional Logic for Woo Product Add-Ons
 * Plugin URI:        https://meow-crew.com/plugin/conditional-logic-for-woocommerce-product-add-ons
 * Description:       Add conditional logic for your Product Add-ons to show or hide certain fields based on other fields' values or states (eg, show Field Х when First Option is selected in Field Y).
 * Version:           2.0.0
 * Author:            Meow Crew
 * Author URI:        https://meow-crew.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       conditional-logic-for-product-addons
 * Domain Path:       /languages
 */

if ( function_exists( 'clfwpao_fs' ) ) {
	clfwpao_fs()->set_basename( false, __FILE__ );
} else {

	if ( ! function_exists( 'clfwpao_fs' ) ) {
		require_once 'license.php';
	}

	call_user_func( function () {

		require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

		$main = new AddonsConditionsPlugin( __FILE__ );

		try {
			$main->run();
		} catch ( Exception $e ) {
			return;
		}
	} );
}
