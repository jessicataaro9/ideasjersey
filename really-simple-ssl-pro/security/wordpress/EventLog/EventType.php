<?php

namespace EventLog;


use RSSLPRO\Security\wordpress\Rsssl_Limit_Login_Attempts;
use RSSSLPRO\Security\wordpress\LimitLogin\GeoLocation;

class EventType {

	public $username;
	/**
	 * @var mixed
	 */
	public $get_ip_address;

	public function __construct( $ip = null ) {
		$lla = new Rsssl_Limit_Login_Attempts();
		if ( is_null( $ip ) ) {
			$this->get_ip_address = filter_var( $lla->get_ip_address()[0], FILTER_VALIDATE_IP );
		} else {
			//first we check if the ip is has a cidr
			if ( strpos( $ip, '/' ) !== false ) {
				//we have a cidr
				$cidr                 = explode( '/', $ip );
				$ip                   = $cidr[0];
				$mask                 = $cidr[1];
				$ip                   = filter_var( $ip, FILTER_VALIDATE_IP );
				$mask                 = filter_var( $mask, FILTER_VALIDATE_INT );
				$this->get_ip_address = $ip . '/' . $mask;
			} else {
				$this->get_ip_address = filter_var( $ip, FILTER_VALIDATE_IP );
			}
		}
	}

	/**
	 * Sets the values needed for the event that is being logged.
	 *
	 * @param  string  $username
	 * @param  string  $code
	 *
	 * @return array
	 */
	public static function login( string $username, string $code ): array {
		//sanitize the username
		$username = sanitize_user( $username );
		$self     = new self();

		return [
			'timestamp'   => time(), //Unix TimeStamp
			'event_id'    => $code,
			'event_type'  => self::get_event_type_by_code( $code ),
			'severity'    => self::get_severity_based_on_code( $code ),
			'username'    => $username,
			'source_ip'   => $self->get_ip_address, //is sanitized in the function
			'description' => self::get_description_based_on_code( $code, $username, $self->get_ip_address ),
			'event_data'  => self::get_event_data_based_on_code( $code, $username, $self->get_ip_address ),
		];
	}

	/**
	 * @param  string  $code
	 * @param  string|null  $ip
	 * @param  string|null  $username
	 *
	 * @return array
	 */
	public static function add_to_block( string $code, string $ip = null, string $username = null , $country = null ): array {
		//sanitize the username
		$username = sanitize_user( $username );
		$self     = new self( $ip );
		//in case of adding a country we check if the country is not null
		if ( $code == '1026' || $code == '1027' ) {
			if ( is_null( $country ) ) {
				return [];
			}
		}

		return [
			'timestamp'   => time(), //Unix TimeStamp
			'event_id'    => $code,
			'event_type'  => self::get_event_type_by_code( $code ),
			'severity'    => self::get_severity_based_on_code( $code ),
			'username'    => $username,
			'source_ip'   => $self->get_ip_address, //is sanitized in the function
			'description' => self::get_description_based_on_code( $code, $username, $self->get_ip_address, $country ),
			'event_data'  => self::get_event_data_based_on_code( $code, $username, $self->get_ip_address, $country ),
		];
	}

	/**
	 * Kept this function for backwards compatibility or even for additional changes.
	 *
	 * @param  string  $username
	 * @param  string  $code
	 *
	 * @return array
	 */
	public static function login_blocked( string $username, string $code ): array {
		return self::login( $username, $code );
	}

	/**
	 * Kept this function for backwards compatibility or even for additional changes.
	 *
	 * @param  string  $username
	 * @param  string  $code
	 *
	 * @return array
	 */
	public static function login_failed_by_user( string $username, $code ): array {
		return self::login( $username, $code );
	}

	/**
	 * Kept this function for backwards compatibility or even for additional changes.
	 *
	 * @param  string  $username
	 * @param  string  $code
	 *
	 * @return array
	 */
	public static function login_failed_by_ip( string $username, $code ): array {
		return self::login( $username, $code );
	}

	/**
	 * @param  string  $code
	 * @param  string|null  $username
	 *
	 * @return string
	 */
	public static function get_description_based_on_code( string $code, string $username = null, $ip = null, $country = null ): string {
		switch ( $code ) {
			case '1000':
				return __( 'Login successful', 'really-simple-ssl' ) . ' (' . __( 'Authentication',
						'really-simple-ssl' ) . ')';
			case '1001':
				return __( 'Login failed (incorrect credentials)', 'really-simple-ssl' ) . ' (' . __( 'Authentication',
						'really-simple-ssl' ) . ')';
			case '1002':
				return __( 'REST API authentication successful', 'really-simple-ssl' ) . ' (' . __( 'Authentication',
						'really-simple-ssl' ) . ')';
			case '1003':
				return __( 'REST API authentication failed', 'really-simple-ssl' ) . ' (' . __( 'Authentication',
						'really-simple-ssl' ) . ')';
			case '1004':
				return __( 'XML-RPC authentication successful', 'really-simple-ssl' ) . ' (' . __( 'Authentication',
						'really-simple-ssl' ) . ')';
			case '1005':
				return __( 'XML-RPC authentication failed', 'really-simple-ssl' ) . ' (' . __( 'Authentication',
						'really-simple-ssl' ) . ')';
			case '1010':
				return sprintf( __( 'User account %s added to temporary blocklist', 'really-simple-ssl' ), $username )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1011':
				return sprintf( __( 'User account %s removed from temporary blocklist', 'really-simple-ssl' ),
						$username )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1012':
				return sprintf( __( 'User account %s added to permanent blocklist', 'really-simple-ssl' ), $username )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1013':
				return sprintf( __( 'User account %s removed from permanent blocklist', 'really-simple-ssl' ),
						$username )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1020':
				return sprintf( __( 'IP address %s added to temporary blocklist', 'really-simple-ssl' ), $ip )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1021':
				return sprintf( __( 'IP address %s removed from temporary blocklist', 'really-simple-ssl' ), $ip )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1022':
				return sprintf( __( 'IP address %s added to permanent blocklist', 'really-simple-ssl' ), $ip )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1023':
				return sprintf( __( 'IP address %s removed from permanent blocklist', 'really-simple-ssl' ), $ip )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1024':
				return sprintf( __( 'IP address %s added to permanent whitelist', 'really-simple-ssl' ), $ip )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1025':
				return sprintf( __( 'IP address %s removed from permanent whitelist', 'really-simple-ssl' ), $ip )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1030':
				return sprintf( __( 'Unblock link sent to %s', 'really-simple-ssl' ), '%email-address%' )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1040':
				return __( 'Login failed (user account or IP address in blocklist)', 'really-simple-ssl' )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1100':
				return __( 'Login failed (incorrect MFA code)', 'really-simple-ssl' ) . ' (' . __( 'MFA',
						'really-simple-ssl' ) . ')';
			case '1110':
				return __( 'MFA setup required', 'really-simple-ssl' ) . ' (' . __( 'MFA', 'really-simple-ssl' ) . ')';
			case '1026':
				return sprintf( __( 'Country %s added to geo-ip blocklist', 'really-simple-ssl' ), self::get_country_by_iso2( $country ) )
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			case '1027':
				return sprintf( __( 'Country %s removed from geo-ip blocklist', 'really-simple-ssl' ), self::get_country_by_iso2( $country ))
				       . ' (' . __( 'Login-protection', 'really-simple-ssl' ) . ')';
			default:
				return __( 'Unknown event', 'really-simple-ssl' );
		}
	}


	/**
	 * Fetches the event type based on the event code.
	 *
	 * @param  string  $code
	 *
	 * @return string
	 */
	public static function get_event_type_by_code( string $code ): string {
		switch ( $code ) {
			case '1001':
			case '1002':
			case '1003':
			case '1004':
			case '1005':
			case '1000':
				return 'authentication';
			case '1011':
			case '1012':
			case '1013':
			case '1020':
			case '1021':
			case '1022':
			case '1023':
			case '1024':
			case '1025':
			case '1030':
			case '1040':
			case '1010':
			case '1026':
			case '1027':
				return 'login-protection';
			case '1110':
			case '1111':
			case '1100':
				return 'MFA';
			default:
				return 'unknown-event';
		}
	}

	/**
	 * Fetches the severity based on the event code.
	 *
	 * @param  string  $code
	 *
	 * @return string
	 */
	public static function get_severity_based_on_code( string $code ): string {
		switch ( $code ) {
			case '1020':
			case '1010':
			case '1005':
			case '1003':
			case '1001':
				return 'warning';
			default:
				return 'informational';
		}
	}

	public static function get_event_data_based_on_code( string $code, string $username, $get_ip_address, $country = null ): string {
		switch ( $code ) {
			case '1000':
			default:
				if ( is_null( $country ) ) {
					return json_encode( [
						'iso2_code' => self::get_iso2_from_ip( $get_ip_address ),
					] );
				}
				return json_encode( [
					'iso2_code' => $country,
				] );
		}
	}

	public static function get_iso2_from_ip( $get_ip_address , $get_named_country = false ): string {
		$code =  GeoLocation::get_county_by_ip( $get_ip_address );
		if ( $get_named_country ) {
			return GeoLocation::get_country_by_iso2( $code );
		}
		return $code;
	}

	private static function get_country_by_iso2( $country ) {
		return GeoLocation::get_country_by_iso2( $country );
	}

}