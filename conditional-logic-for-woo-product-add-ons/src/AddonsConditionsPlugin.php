<?php namespace MeowCrew\AddonsConditions;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Exception;
use MeowCrew\AddonsConditions\Core\AdminNotifier;
use MeowCrew\AddonsConditions\Core\FileManager;
use MeowCrew\AddonsConditions\Core\ServiceContainerTrait;
use MeowCrew\AddonsConditions\Admin\Admin;
use MeowCrew\AddonsConditions\Frontend\Frontend;

/**
 * Class AddonsConditionsPlugin
 *
 * @package MeowCrew\AddonsConditions
 */
class AddonsConditionsPlugin {

	use ServiceContainerTrait;

	const VERSION = '2.0.0';

	/**
	 * AddonsConditionsPlugin constructor.
	 *
	 * @param  string  $mainFile
	 */
	public function __construct( $mainFile ) {
		FileManager::init( $mainFile, 'conditional-logic-for-product-addons' );

		add_action( 'before_woocommerce_init', function () use ( $mainFile ) {
			if ( class_exists( FeaturesUtil::class ) ) {
				FeaturesUtil::declare_compatibility( 'custom_order_tables', $mainFile, true );
			}
		} );
	}

	/**
	 * Run plugin part
	 *
	 * @throws Exception
	 */
	public function run() {
		$this->getContainer()->add( 'fileManager', FileManager::getInstance() );
		$this->getContainer()->add( 'adminNotifier', new AdminNotifier() );

		if ( $this->checkRequirements() ) {

			$this->getContainer()->add( 'admin.service', new Admin() );
			$this->getContainer()->add( 'frontend.service', new Frontend() );

			add_action( 'plugins_loaded', array( $this, 'loadTextDomain' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( $this->getContainer()->getFileManager()->getMainFile() ),
				array( $this, 'addPluginAction' ), 10, 4 );
		}

	}

	/**
	 * Load plugin translations
	 */
	public function loadTextDomain() {
		$name = $this->getContainer()->getFileManager()->getPluginName();
		load_plugin_textdomain( 'conditional-logic-for-product-addons', false, $name . '/languages/' );
	}

	public function checkRequirements() {
		/* translators: %s = required plugin */
		$message = __( 'Conditions for Product Add-ons requires %s plugin to be active!',
			'conditional-logic-for-product-addons' );

		$plugins = $this->getRequiredPluginsToBeActive();

		if ( count( $plugins ) ) {

			foreach ( $plugins as $plugin ) {
				$error = sprintf( $message, $plugin );
				$this->getContainer()->getAdminNotifier()->push( $error, AdminNotifier::ERROR, false );
			}

			return false;
		}

		return true;
	}

	/**
	 * Add setting to plugin actions at plugins list
	 *
	 * @param  array  $actions
	 *
	 * @return array
	 */
	public function addPluginAction( $actions ) {

		$actions[] = '<a target="_blank" href="' . self::getDocumentationURL() . '">' . __( 'Documentation',
				'tier-pricing-table' ) . '</a>';

		if ( ! clfwpao_fs()->is_anonymous() && clfwpao_fs()->is_installed_on_site() ) {
			$actions[] = '<a href="' . self::getAccountPageURL() . '"><b style="color: green">' . __( 'Account',
					'tier-pricing-table' ) . '</b></a>';
		}

		$actions[] = '<a href="' . self::getContactUsURL() . '"><b style="color: green">' . __( 'Contact Us',
				'tier-pricing-table' ) . '</b></a>';

		if ( ! clfwpao_fs()->is_premium() ) {
			$actions[] = '<a href="' . ( clfwpao_fs()->is_activation_mode() ? clfwpao_fs()->get_activation_url() : clfwpao_fs()->get_upgrade_url() ) . '"><b style="color: red">' . __( 'Go Premium',
					'tier-pricing-table' ) . '</b></a>';
		}

		return $actions;
	}

	public function getRequiredPluginsToBeActive() {

		$plugins = array();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( ! ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) ) {
			$plugins[] = 'WooCommerce';
		}

		if ( ! ( is_plugin_active( 'woocommerce-product-addons/woocommerce-product-addons.php' ) || is_plugin_active_for_network( 'woocommerce-product-addons/woocommerce-product-addons.php' ) ) ) {
			$plugins[] = 'WooCommerce Product Add-ons';
		}

		return $plugins;
	}


	public static function getDocumentationURL() {
		return 'https://meow-crew.com/documentation/conditional-logic-for-woocommerce-product-add-ons-documentation ';
	}

	public static function getContactUsURL() {
		return admin_url( 'admin.php?page=clfwpao-contact-us' );
	}

	public static function getAccountPageURL() {
		return admin_url( 'admin.php?page=clfwpao-account' );
	}
}
