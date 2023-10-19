<?php namespace MeowCrew\AddonsConditions\Core;

use function \extract as allowedExtract;

class FileManager {

	/**
	 * Main file
	 *
	 * @var string
	 */
	private $mainFile;

	/**
	 * Directory of the plugin
	 *
	 * @var string
	 */
	private $pluginDirectory;

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $pluginName;

	/**
	 * Plugin url
	 *
	 * @var string
	 */
	private $pluginUrl;

	/**
	 * Theme directory
	 *
	 * @var string
	 */
	private $themeDirectory;

	/**
	 * Instance
	 *
	 * @var self
	 */
	private static $instance;

	private function __construct() {
	}

	/**
	 * PluginManager initialization.
	 *
	 * @param string $mainFile
	 * @param string|null $themeDirectory
	 */
	public static function init( $mainFile, $themeDirectory = null ) {

		self::$instance = new self();

		self::$instance->mainFile        = $mainFile;
		self::$instance->pluginDirectory = plugin_dir_path( self::$instance->mainFile );
		self::$instance->pluginName      = basename( self::$instance->pluginDirectory );
		self::$instance->themeDirectory  = $themeDirectory ? $themeDirectory : self::$instance->pluginName;
		self::$instance->pluginUrl       = plugin_dir_url( self::$instance->getMainFile() );
	}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			throw new \Exception( 'FileManager was not init.' );
		}

		return self::$instance;
	}

	/**
	 * Get the plugin directory
	 *
	 * @return string
	 */
	public function getPluginDirectory() {
		return $this->pluginDirectory;
	}

	/**
	 * Return name of the plugin
	 *
	 * @return string
	 */
	public function getPluginName() {
		return $this->pluginName;
	}

	/**
	 * Get the main file
	 *
	 * @return string
	 */
	public function getMainFile() {
		return $this->mainFile;
	}

	/**
	 * Get the plugin url
	 *
	 * @return string
	 */
	public function getPluginUrl() {
		return $this->pluginUrl;
	}

	/**
	 * Include template
	 *
	 * @param string $__template
	 * @param array $__variables
	 * @param bool $isFullPath
	 */
	public function includeTemplate( $__template, array $__variables = array(), $isFullPath = false ) {
		if ( ! $isFullPath ) {
			$__template = $this->locateTemplate( $__template );
		}

		if ( $__template ) {

			call_user_func( function ( $__template, $__variables ) {
				allowedExtract( $__variables );
				/**
				 * Before including template
				 *
				 * @since 1.0.0
				 **/
				do_action( 'product_addons_conditions/template/before_render', $__template, $__variables );

				include( $__template );

				/**
				 * After including template
				 *
				 * @since 1.0.0
				 **/
				do_action( 'product_addons_conditions/template/after_render', $__template, $__variables );
			}, $__template, $__variables );
		}
	}

	/**
	 * Render template
	 *
	 * @param string $template
	 * @param array $variables
	 * @param bool $isFullPath
	 *
	 * @return string
	 */
	public function renderTemplate( $template, array $variables = array(), $isFullPath = false ) {
		ob_start();
		$this->includeTemplate( $template, $variables, $isFullPath );
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Locate assets
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public function locateAsset( $file ) {
		return $this->pluginUrl . 'assets/' . $file;
	}

	/**
	 * Locate template
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function locateTemplate( $template ) {

		$file = $this->pluginDirectory . 'views/' . $template;

		if ( strpos( $template, 'frontend/' ) === 0 ) {

			$frontendTemplate = str_replace( 'frontend/', '', $template );
			$frontendFile     = locate_template( $this->themeDirectory . '/' . $frontendTemplate );

			if ( $frontendFile ) {
				$file = $frontendFile;
			}
		}

		/**
		 * Filtering template location. 3rd-party plugin can override templates using this hook
		 *
		 * @since 1.0.0
		 **/
		return apply_filters( 'product_addons_conditions/template/location', $file, $template );
	}

}
