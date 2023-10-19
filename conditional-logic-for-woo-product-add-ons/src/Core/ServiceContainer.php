<?php namespace MeowCrew\AddonsConditions\Core;

use Exception;

class ServiceContainer {

	private $services = array();

	private static $instance;

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function add( $name, $instance ) {

		/**
		 * Allows to modify container service
		 *
		 * @since 1.0.0
		 **/
		$instance = apply_filters( 'product_addons_conditions/container/service_instance', $instance, $name );

		$this->services[ $name ] = $instance;
	}

	/**
	 * Get service
	 *
	 * @param $name
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get( $name ) {
		if ( ! empty( $this->services[ $name ] ) ) {
			return $this->services[ $name ];
		}

		throw new Exception( 'Undefined service' );
	}

	/**
	 * Get fileManager
	 *
	 * @return FileManager
	 */
	public function getFileManager() {
		try {
			return $this->get( 'fileManager' );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Get AdminNotifier
	 *
	 * @return AdminNotifier
	 */
	public function getAdminNotifier() {
		try {
			return $this->get( 'adminNotifier' );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Get Logger
	 *
	 * @return Logger|null
	 */
	public function getLogger() {
		try {
			return $this->get( 'logger' );
		} catch ( Exception $e ) {
			return null;
		}
	}

}
