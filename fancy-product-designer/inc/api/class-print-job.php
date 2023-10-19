<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Print_Job') ) {

	class FPD_Print_Job {

		public $id;
		private $get_by_guid = false;
		private $id_selector = 'ID';
		private $id_format = '%d';

		public function __construct( $id, $get_by_guid=false ) {

			$this->id = $id;
			$this->get_by_guid = $get_by_guid;
			$this->id_selector = $get_by_guid ? 'guid' : 'ID';
			$this->id_format = $get_by_guid ? '%s' : '%d';

		}

		public static function create( $data = array() ) {

			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();

			//create products table if necessary
			if( !fpd_table_exists(FPD_PRINT_JOBS_TABLE) ) {
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

				//create products table
				$sql_string = "ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				              data TEXT COLLATE utf8_general_ci NULL,
				              status TEXT COLLATE utf8_general_ci NULL,
							  guid TEXT COLLATE utf8_general_ci NOT NULL,
							  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ,
							  PRIMARY KEY (ID)";

				$sql = "CREATE TABLE ".FPD_PRINT_JOBS_TABLE." ($sql_string) $charset_collate;";
				dbDelta($sql);

			}

			$guid = uniqid();

			$inserted = $wpdb->insert(
				FPD_PRINT_JOBS_TABLE,
				array(
					'data' 	=> is_array($data) ? json_encode( $data ) : $data,
					'guid' => $guid,
					'status' 	=> 'processing'
				),
				array( '%s', '%s', '%s' )
			);

			return $inserted ? $guid : false;

		}

		public static function exists( $id, $id_selector='ID', $id_format='%d' ) {

			if( fpd_table_exists(FPD_PRINT_JOBS_TABLE) ) {

				global $wpdb;
				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM ".FPD_PRINT_JOBS_TABLE." WHERE ".$id_selector."=".$id_format, $id ) );
				return $count === "1";

			}
			else {
				return false;
			}

		}

		public static function get_print_jobs() {

			global $wpdb;

			return $products;

		}

		public function update( $data=null, $status=null ) {

			global $wpdb;

			$columns = array();
			$colum_formats = array();

			if( !is_null( $data ) ) {
				$columns['data'] = is_array($data) ? json_encode($data) : $data;
				array_push($colum_formats, '%s');
			}

			if( !is_null( $status ) ) {
				$columns['status'] = $status;
				array_push($colum_formats, '%s');
			}

			if( !empty($columns) ) {

				$wpdb->update(
					FPD_PRINT_JOBS_TABLE,
				 	$columns, //what
				 	array($this->id_selector => $this->id), //where
				 	$colum_formats, //format what
				 	array($this->id_format) //format where
				);

			}

			return $columns;

		}

		public function delete() {

			global $wpdb;

			try {

				$wpdb->query( $wpdb->prepare("DELETE FROM ".FPD_PRINT_JOBS_TABLE." WHERE ".$this->id_selector."=".$this->id_format, $this->id) );

				return 1;
			}
			catch(Exception $e) {
				return 0;
			}

		}

		public function get_data( ) {

			global $wpdb;

			fpd_logger($this->id_selector);
			fpd_logger($this->id);
			$data = $wpdb->get_var(
				$wpdb->prepare( "SELECT data FROM ".FPD_PRINT_JOBS_TABLE." WHERE ".$this->id_selector."=".$this->id_format, $this->id)
			);

			if( $data ) {

				return json_decode($data, true);

			}
			
			return array();

		}

		public function get_local_file( ) {

			global $wpdb;

			$data = $this->get_data();

			if( $data ) {

				if( is_array($data) && isset($data['local_file']) )
					return $data['local_file'];

			}
			
			return null;

		}

	}

}

?>