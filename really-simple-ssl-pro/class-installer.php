<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Install suggested plugins
 */


if ( !class_exists('rsssl_installer') ){
	class rsssl_installer {
		private $slug;
		public function __construct($slug) {
			if ( !current_user_can('install_plugins') ) return;

			$this->slug = $slug;
			if (!$this->plugin_is_downloaded() || !$this->plugin_is_activated() ) {
				add_action( 'wp_ajax_rsssl_download_plugin', array($this, 'download_plugin') );
				add_action( 'wp_ajax_rsssl_activate_plugin', array($this, 'activate_plugin') );
			}
		}

		/**
		 * Check if plugin is downloaded
		 * @return bool
		 */

		private function plugin_is_downloaded(){
			return file_exists(trailingslashit(WP_PLUGIN_DIR).$this->get_activation_slug() );
		}
		/**
		 * Check if plugin is activated
		 * @return bool
		 */
		private function plugin_is_activated(){
			return is_plugin_active($this->get_activation_slug() );
		}

		/**
		 * Install plugin
		 * @param string $step
		 *
		 * @return void
		 */
		public function install($step){
			if ( !current_user_can('install_plugins')) return;

			if ( $step === 'download' ) {
				$this->download_plugin();
			}
			if ( $step === 'activate' ) {
				$this->activate_plugin();
			}
		}

		/**
		 * Get slug to activate plugin with
		 * @return string
		 */
		public function get_activation_slug(){
			$slugs = [
				'really-simple-ssl' => 'really-simple-ssl/rlrsssl-really-simple-ssl.php',
			];
			return $slugs[$this->slug];
		}

		/**
		 * Download the plugin
		 * @return void
		 */
		public function download_plugin() {
			if ( !current_user_can('install_plugins')) return;

			if ( !get_transient("rsssl_plugin_download_active") ) {
				set_transient("rsssl_plugin_download_active", 5 * MINUTE_IN_SECONDS );
				$info          = $this->get_plugin_info();
				$download_link = esc_url_raw( $info->versions['trunk'] );
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

				$skin     = new WP_Ajax_Upgrader_Skin();
				$upgrader = new Plugin_Upgrader( $skin );
				$upgrader->install( $download_link );
				delete_transient("rsssl_plugin_download_active");
			}
		}

		/**
		 * Activate the plugin
		 *
		 * @return void
		 */
		public function activate_plugin() {
			if ( !current_user_can('install_plugins')) return;
			activate_plugin( $this->get_activation_slug() );
		}

		/**
		 * Get plugin info
		 * @return array|WP_Error
		 */
		public function get_plugin_info()
		{
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			$plugin_info = get_transient('rsssl_'.$this->slug . '_plugin_info');
			if ( empty($plugin_info) ) {
				$plugin_info = plugins_api('plugin_information', array('slug' => $this->slug));
				if (!is_wp_error($plugin_info)) {
					set_transient('rsssl_'.$this->slug . '_plugin_info', $plugin_info, WEEK_IN_SECONDS);
				}
			}
			return $plugin_info;
		}
	}

}