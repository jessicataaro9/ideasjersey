<?php

namespace RSSLPRO\Security\wordpress;

use EventLog\EventType;
use Exception;
use RSSSLPRO\Security\wordpress\LimitLogin\GeoLocation;
use RSSSLPRO\Security\wordpress\LimitLogin\LoginAttempt;
use RSSSLPRO\Security\wordpress\rsssl_event_log;
use security\wordpress\DynamicTables\ArrayQueryBuilder;
use security\wordpress\DynamicTables\DataTable;
use security\wordpress\DynamicTables\QueryBuilder;
use security\wordpress\DynamicTables\rsssl_dataTable;
use stdClass;

require_once rsssl_pro_path . 'bin/DynamicTables/DataTable.php';
require_once rsssl_pro_path . 'bin/DynamicTables/QueryBuilder.php';
require_once rsssl_pro_path . 'bin/DynamicTables/ArrayQueryBuilder.php';

if ( ! class_exists( 'Rsssl_Limit_Login_Attempts' ) ) {
	class Rsssl_Limit_Login_Attempts {

		const CACHE_EXPIRATION = 3600;
		const EVENT_CODE_USER_BLOCKED = '1012';
		const EVENT_CODE_USER_UNBLOCKED = '1013';
		const EVENT_CODE_IP_BLOCKED = '1022';
		const EVENT_CODE_IP_UNBLOCKED = '1023';

		const EVENT_CODE_IP_ADDED_TO_ALLOWLIST = '1024';
		const EVENT_CODE_IP_REMOVED_FROM_ALLOWLIST = '1025';
		const EVENT_CODE_USER_ADDED_TO_ALLOWLIST = '9999';
		const EVENT_CODE_USER_REMOVED_FROM_ALLOWLIST = '1025';
		const EVENT_CODE_IP_UNLOCKED = '1021';
		const EVENT_CODE_USER_LOCKED = '1011';
		const EVENT_CODE_USER_UNLOCKED = '1012';
		const EVENT_CODE_IP_LOCKED = '1020';
		const EVENT_CODE_IP_UNLOCKED_BY_ADMIN = '1021';
		const EVENT_CODE_COUNTRY_BLOCKED = '1026';
		const EVENT_CODE_COUNTRY_UNBLOCKED = '1027';


		public function __construct() {
		}

		/**
		 *
		 * Process the request. Get the IP address(es) and check if they are present in the allowlist / blocklist.
		 *
		 * @return void
		 */
		public function check_request(): string {
			$ips = $this->get_ip_address();

			return $this->check_ip_address( $ips );
		}

		public function check_request_for_user( string $username ): string {
			$usernames = [ $username ];

			return $this->check_against_users( $usernames );
		}

		public function check_request_for_country() {
			$country = GeoLocation::get_county_by_ip( $this->get_ip_address()[0] );

			return $this->check_against_countries( [ $country ] );
		}

		/**
		 * Retrieves a list of unique, validated IP addresses from various headers.
		 *
		 * This function attempts to retrieve the client's IP address from a variety of HTTP headers,
		 * including 'X-Forwarded-For', 'X-Forwarded', 'Forwarded-For', and 'Forwarded'. The function
		 * prefers rightmost IPs in these headers as they are less likely to be spoofed. It also checks
		 * if each IP is valid and not in a private or reserved range. Duplicate IP addresses are removed
		 * from the returned array.
		 *
		 * Note: While this function strives to obtain accurate IP addresses, the nature of HTTP headers
		 * means that it cannot guarantee the authenticity of the IP addresses.
		 *
		 * @return array An array of unique, validated IP addresses. If no valid IP addresses are found,
		 *               an empty array is returned.
		 */

		public function get_ip_address(): array {
			// Initialize an array to hold all discovered IP addresses
			$ip_addresses = [];
			// Initialize a variable to hold the rightmost IP address
			$rightmost_ip = null;

			// Define an array of headers to check for possible client IP addresses
			$headers_to_check = array(
				'REMOTE_ADDR',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',
				'HTTP_CF_CONNECTING_IP',
				'HTTP_FASTLY_CLIENT_IP',
				'HTTP_X_CLUSTER_CLIENT_IP',
				'HTTP_X_REAL_IP',
				'True-Client-IP',
			);

			// Loop through each header
			foreach ( $headers_to_check as $header ) {
				// If the header exists in the $_SERVER array
				if ( isset( $_SERVER[ $header ] ) ) {
					// Remove all spaces from the header value and explode it by comma
					// to get a list of IP addresses
					$ips = explode( ',', str_replace( ' ', '', $_SERVER[ $header ] ) );

					// Reverse the array to process rightmost IP first, which is less likely to be spoofed
					$ips = array_reverse( $ips );

					// Loop through each IP address in the list
					foreach ( $ips as $ip ) {
						$ip = trim( $ip );

						// If the IP address is valid and does not belong to a private or reserved range
						if ( filter_var( $ip,
							FILTER_VALIDATE_IP ) ) {//, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
							// Add the IP address to the array
							$ip_addresses[] = $ip;

							// If we haven't stored a rightmost IP yet, store this one
							if ( $rightmost_ip === null ) {
								$rightmost_ip = $ip;
							}
						}
					}
				}
			}

			// If we found a rightmost IP address
			if ( $rightmost_ip !== null ) {
				// Get all keys in the IP addresses array that match the rightmost IP
				$rightmost_ip_keys = array_keys( $ip_addresses, $rightmost_ip );

				// Loop through each key
				foreach ( $rightmost_ip_keys as $key ) {
					// If this is not the first instance of the rightmost IP
					if ( $key > 0 ) {
						// Remove this instance from the array
						unset( $ip_addresses[ $key ] );
					}
				}
			}

			return array_values( array_unique( $ip_addresses ) );
		}

		/**
		 * Processes an IP or range and calls the appropriate function.
		 *
		 * This function determines whether the provided input is an IP address or an IP range,
		 * and then calls the appropriate function accordingly.
		 *
		 * @param $ip_addresses
		 *
		 * @return string Returns a status representing the check result: 'allowed' for allowlist hit, 'blocked' for blocklist hit, 'not found' for no hits.
		 */
		public function check_ip_address( $ip_addresses ): string {
			$found_blocked_ip = false;
			foreach ( $ip_addresses as $ip ) {
				// Remove any white space around the input
				$item = trim( $ip );
				// Validate the input to determine whether it's an IP or a range
				if ( filter_var( $item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
					// It's a valid IP address
					$status = $this->check_against_ips( [ $item ] );
					// If not found in regular IP's, check against ranges
					if ( $status === 'not_found' ) {
						$status = $this->get_ip_range_status( [ $item ] );
					}

					if ( $status === 'allowed' ) {
						return 'allowed';
					}

					if ( $status === 'blocked' ) {
						$found_blocked_ip = true;
					}
				}
			}

			if ( $found_blocked_ip ) {
				return 'blocked';
			}

			return 'not_found';

		}

		/**
		 * Checks if a given IP address is within a specified IP range.
		 *
		 * This function supports both IPv4 and IPv6 addresses, and can handle ranges in
		 * both standard notation (e.g. "192.0.2.0") and CIDR notation (e.g. "192.0.2.0/24").
		 *
		 * In CIDR notation, the function uses a bitmask to check if the IP address falls within
		 * the range. For IPv4 addresses, it uses the `ip2long()` function to convert the IP
		 * address and subnet to their integer representations, and then uses the bitmask to
		 * compare them. For IPv6 addresses, it uses the `inet_pton()` function to convert the IP
		 * address and subnet to their binary representations, and uses a similar bitmask approach.
		 *
		 * If the range is not in CIDR notation, it simply checks if the IP equals the range.
		 *
		 * @param  string  $ip  The IP address to check.
		 * @param  string  $range  The range to check the IP address against.
		 *
		 * @return bool True if the IP address is within the range, false otherwise.
		 */
		public function ip_in_range( string $ip, string $range ): bool {
			// Check if the IP address is properly formatted
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
				error_log( "$ip not in $range" );

				return false;
			}
			// Check if the range is in CIDR notation
			if ( strpos( $range, '/' ) !== false ) {
				// The range is in CIDR notation, so we split it into the subnet and the bit count
				[ $subnet, $bits ] = explode( '/', $range );

				if ( ! is_numeric( $bits ) || $bits < 0 || $bits > 128 ) {
					error_log( "$range invalid CIDR notation" );

					return false;
				}

				// Check if the subnet is a valid IPv4 address
				if ( filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
					// Convert the IP address and subnet to their integer representations
					$ip     = ip2long( $ip );
					$subnet = ip2long( $subnet );

					// Create a mask based on the number of bits
					$mask = - 1 << ( 32 - $bits );

					// Apply the mask to the subnet
					$subnet &= $mask;

					// Compare the masked IP address and subnet
					return ( $ip & $mask ) === $subnet;
				}

				// Check if the subnet is a valid IPv6 address
				if ( filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					// Convert the IP address and subnet to their binary representations
					$ip     = inet_pton( $ip );
					$subnet = inet_pton( $subnet );
					// Divide the number of bits by 8 to find the number of full bytes
					$full_bytes = floor( $bits / 8 );
					// Find the number of remaining bits after the full bytes
					$partial_byte = $bits % 8;
					// Initialize the mask
					$mask = '';
					// Add the full bytes to the mask, each byte being "\xff" (255 in binary)
					$mask .= str_repeat( "\xff", $full_bytes );
					// If there are any remaining bits...
					if ( $partial_byte !== 0 ) {
						// Add a byte to the mask with the correct number of 1 bits
						// First, create a string with the correct number of 1s
						// Then, pad the string to 8 bits with 0s
						// Convert the binary string to a decimal number
						// Convert the decimal number to a character and add it to the mask
						$mask .= chr( bindec( str_pad( str_repeat( '1', $partial_byte ), 8, '0' ) ) );
					}

					// Fill in the rest of the mask with "\x00" (0 in binary)
					// The total length of the mask should be 16 bytes, so subtract the number of bytes already added
					// If we added a partial byte, we need to subtract 1 more from the number of bytes to add
					$mask .= str_repeat( "\x00", 16 - $full_bytes - ( $partial_byte != 0 ? 1 : 0 ) );

					// Compare the masked IP address and subnet
					return ( $ip & $mask ) === $subnet;
				}

				// The subnet was not a valid IP address
				error_log( "$range invalid CIDR notation" );

				return false;
			}

			if ( ! filter_var( $range, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
				// The range was not in CIDR notation and was not a valid IP address
				error_log( "$range invalid CIDR notation" );

				return false;
			}

			// The range is not in CIDR notation, so we simply check if the IP equals the range
			return $ip === $range;
		}

		/**
		 * Checks a list of IP addresses against allowlist and blocklist.
		 *
		 * This function fetches explicit IP addresses from the database tables and checks if the supplied IPs are in the allowlist or blocklist.
		 * If an IP is found in the allowlist or blocklist, it is stored in the corresponding database table and a status is returned.
		 *
		 * @param  array  $ip_addresses  The list of IP addresses to check.
		 *
		 * @return string|null Status representing the check result: 'allowed' for allowlist hit, 'blocked' for blocklist hit, 'not found' for no hits.
		 */
		public function check_against_ips( array $ip_addresses ): string {

			global $wpdb;

			$cache_key_allowlist = 'rsssl_allowlist_ips';
			$cache_key_blocklist = 'rsssl_blocklist_ips';

			// Try to get the lists from cache
			$allowlist_ips = wp_cache_get( $cache_key_allowlist );
			$blocklist_ips = wp_cache_get( $cache_key_blocklist );

			// If not cached, fetch from the database and then cache
			if ( $allowlist_ips === false ) {
//				$allowlist_ips = $wpdb->get_col( "SELECT ip_or_range FROM {$wpdb->prefix}rsssl_allowlist WHERE ip_or_range NOT LIKE '%/%'" );
				$allowlist_ips = $wpdb->get_col( "SELECT attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE status = 'allowed' AND attempt_type = 'source_ip' AND  attempt_value NOT LIKE '%/%'" );

				wp_cache_set( $cache_key_allowlist, $allowlist_ips, null, self::CACHE_EXPIRATION );
			}

			if ( $blocklist_ips === false ) {
//				$blocklist_ips = $wpdb->get_col( "SELECT ip_or_range FROM {$wpdb->prefix}rsssl_blocklist WHERE ip_or_range NOT LIKE '%/%'" );
				$blocklist_ips = $wpdb->get_col( "SELECT attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE status = 'allowed' AND attempt_type = 'source_ip' AND  attempt_value NOT LIKE '%/%'" );

				wp_cache_set( $cache_key_blocklist, $blocklist_ips, null, self::CACHE_EXPIRATION );
			}

			// Check the IP addresses
			foreach ( $ip_addresses as $ip ) {
				if ( in_array( $ip, $allowlist_ips, true ) ) {
					error_log( "$ip found as regular IP in allowlist" );

					return 'allowed';
				}
				if ( in_array( $ip, $blocklist_ips, true ) ) {
					error_log( "$ip found as regular IP in blocklist" );

					return 'blocked';
				}
			}

			return 'not_found';
		}

		public function check_against_users( array $usernames ): string {

			global $wpdb;

			$cache_key_allowlist = 'rsssl_allowlist_users';
			$cache_key_blocklist = 'rsssl_blocklist_users';

			// Try to get the lists from cache
			$allowlist_users = wp_cache_get( $cache_key_allowlist );
			$blocklist_users = wp_cache_get( $cache_key_blocklist );

			// If not cached, fetch from the database and then cache
			if ( $allowlist_users === false ) {
				$allowlist_users = $wpdb->get_col( "SELECT attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE status = 'allowed' AND attempt_type = 'username' " );
				wp_cache_set( $cache_key_allowlist, $allowlist_users, null, self::CACHE_EXPIRATION );
			}

			if ( $blocklist_users === false ) {
				$blocklist_users = $wpdb->get_col( "SELECT attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE status = 'blocked' AND attempt_type = 'username' " );
				wp_cache_set( $cache_key_blocklist, $blocklist_users, null, self::CACHE_EXPIRATION );
			}

			//Check the users
			foreach ( $usernames as $username ) {
				if ( in_array( $username, $allowlist_users, true ) ) {
					return 'allowed';
				}
				if ( in_array( $username, $blocklist_users, true ) ) {
					return 'blocked';
				}
			}

			return 'not_found';
		}

		public function check_against_countries( array $countries ) {
			global $wpdb;

			$cache_key_allowlist = 'rsssl_allowlist_countries';
			$cache_key_blocklist = 'rsssl_blocklist_countries';

			// Try to get the lists from cache
			$allowlist_countries = wp_cache_get( $cache_key_allowlist );
			$blocklist_countries = wp_cache_get( $cache_key_blocklist );

			// If not cached, fetch from the database and then cache
			if ( $allowlist_countries === false ) {
				$allowlist_countries = $wpdb->get_col( "SELECT attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE status = 'allowed' AND attempt_type = 'country' " );
				wp_cache_set( $cache_key_allowlist, $allowlist_countries, null, self::CACHE_EXPIRATION );
			}

			if ( $blocklist_countries === false ) {
				$blocklist_countries = $wpdb->get_col( "SELECT attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE status = 'blocked' AND attempt_type = 'country' " );
				wp_cache_set( $cache_key_blocklist, $blocklist_countries, null, self::CACHE_EXPIRATION );
			}

			//Check the countries
			foreach ( $countries as $country ) {
				if ( in_array( $country, $allowlist_countries, true ) ) {
					return 'allowed';
				}
				if ( in_array( $country, $blocklist_countries, true ) ) {
					return 'blocked';
				}
			}

			return 'not_found';
		}

		/**
		 * Checks a list of IP addresses against allowlist and blocklist ranges.
		 *
		 * This function fetches IP ranges from the database tables and checks if the supplied IPs are within the allowlist or blocklist ranges.
		 * If an IP is found in the allowlist or blocklist range, it is stored in the corresponding database table and a status is returned.
		 *
		 * @param  array  $ip_addresses  The list of IP addresses to check.
		 *
		 * @return string|null Status representing the check result: 'allowed' for allowlist hit, 'blocked' for blocklist hit, 'not found' for no hits.
		 */
		public function get_ip_range_status( array $ip_addresses ): string {

			global $wpdb;

			$cache_key_allowlist_ranges = 'rsssl_allowlist_ranges';
			$cache_key_blocklist_ranges = 'rsssl_blocklist_ranges';

			// Try to get the lists from cache
			$allowlist_ranges = wp_cache_get( $cache_key_allowlist_ranges );
			$blocklist_ranges = wp_cache_get( $cache_key_blocklist_ranges );

			// If not cached, fetch from the database and then cache
			if ( $allowlist_ranges === false ) {
				$allowlist_ranges = $wpdb->get_col( "SELECT attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_type = 'source_ip' AND status = 'allowed' AND attempt_value LIKE '%/%'" );
				wp_cache_set( $cache_key_allowlist_ranges, $allowlist_ranges, null, self::CACHE_EXPIRATION );
			}

			if ( $blocklist_ranges === false ) {
				$blocklist_ranges = $wpdb->get_col( "SELECT attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_type = 'source_ip' AND status = 'blocked' AND attempt_value LIKE '%/%'" );
				wp_cache_set( $cache_key_blocklist_ranges, $blocklist_ranges, null, self::CACHE_EXPIRATION );
			}

			// Check the IP addresses
			foreach ( $ip_addresses as $ip ) {
				foreach ( $allowlist_ranges as $range ) {
					if ( $this->ip_in_range( $ip, $range ) ) {
						//	error_log( "$ip found in $range allowlist range" );

						return 'allowed';
					}
				}
				foreach ( $blocklist_ranges as $range ) {
					if ( $this->ip_in_range( $ip, $range ) ) {
						//	error_log( "$ip found in $range blocklist range" );

						return 'blocked';
					}
				}
			}

			return 'not_found';
		}

		/**
		 * Adds an IP address to the allowlist.
		 *
		 * @param  string  $ip  The IP address to add.
		 */
		public function add_to_allowlist( string $ip ): void {

			if ( ! rsssl_user_can_manage() ) {
				return;
			}

			global $wpdb;

			$wpdb->insert(
				$wpdb->prefix . 'rsssl_allowlist',
				[
					'ip_or_range' => filter_var( $ip, FILTER_VALIDATE_IP,
						FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )
				],
				[ '%s ' ]
			);

			$this->invalidate_cache( 'rsssl_allowlist', $ip );
		}

		/**
		 * Adds an IP address to the blocklist.
		 *
		 * @param  string  $ip  The IP address to add.
		 */
		public function add_to_blocklist( string $ip ): void {

			if ( ! rsssl_user_can_manage() ) {
				return;
			}

			global $wpdb;

			$wpdb->insert(
				$wpdb->prefix . 'rsssl_blocklist',
				[
					'ip_or_range' => filter_var( $ip, FILTER_VALIDATE_IP,
						FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )
				],
				[ '%s ' ]
			);

			// Invalidate the blocklist cache
			$this->invalidate_cache( 'rsssl_blocklist', $ip );
		}

		/**
		 * Removes an IP address from the allowlist.
		 *
		 * @param  string  $ip  The IP address to remove.
		 */
		public function remove_from_allowlist( string $ip ): void {

			if ( ! rsssl_user_can_manage() ) {
				return;
			}

			global $wpdb;

			$wpdb->delete(
				$wpdb->prefix . 'rsssl_allowlist',
				[
					'ip_or_range' => filter_var( $ip, FILTER_VALIDATE_IP,
						FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )
				],
				[ '%s ' ]
			);

			// Invalidate the allowlist cache
			$this->invalidate_cache( 'rsssl_allowlist', $ip );
		}

		/**
		 * Removes an IP address from the blocklist.
		 *
		 * @param  string  $ip  The IP address to remove.
		 */
		public function remove_from_blocklist( string $ip ): void {

			if ( ! rsssl_user_can_manage() ) {
				return;
			}

			global $wpdb;

			$wpdb->delete(
				$wpdb->prefix . 'rsssl_blocklist',
				[
					'ip_or_range' => filter_var( $ip, FILTER_VALIDATE_IP,
						FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE )
				],
				[ '%s ' ]
			);

			// Invalidate the blocklist cache
			$this->invalidate_cache( 'rsssl_blocklist', $ip );
		}

		/**
		 * Invalidates the cache for the specified table and IP address.
		 *
		 * This function clears the cache for the allowlist or blocklist based on the provided table and IP address.
		 * If the IP address is a range, it clears the cache for the corresponding range cache key. Otherwise, it clears
		 * the cache for the corresponding IP cache key.
		 *
		 * @param  string  $table  The table name ('rsssl_allowlist' or 'rsssl_blocklist').
		 * @param  string  $ip  The IP address or range.
		 *
		 * @return void
		 */
		public function invalidate_cache( string $table, string $ip ): void {

			if ( $table === 'rsssl_allowlist' ) {
				// Check if range or IP
				if ( strpos( $ip, '/' ) !== false ) {
					wp_cache_delete( 'rsssl_allowlist_ranges' );
				} else {
					wp_cache_delete( 'rsssl_allowlist_ips' );
				}
			}

			if ( $table === 'rsssl_blocklist' ) {
				if ( strpos( $ip, '/' ) !== false ) {
					wp_cache_delete( 'rsssl_blocklist_ranges' );
				} else {
					wp_cache_delete( 'rsssl_blocklist_ips' );
				}
			}
		}

		public static function IPv62Binary( $ip ) {
			$ip  = inet_pton( $ip );
			$bin = '';
			for ( $bit = strlen( $ip ) - 1; $bit >= 0; $bit -- ) {
				$bin = sprintf( '%08b', ord( $ip[ $bit ] ) ) . $bin;
			}

			return $bin;
		}

		public static function calculate_cidr_from_range( $ip1, $ip2 ) {
			// Determine if ip is v4 or v6
			$isIPv6 = strpos( $ip1, ':' ) !== false;
			$ip1    = trim( $ip1 );
			$ip2    = trim( $ip2 );
			$cidr   = 0;

			if ( $isIPv6 ) {
				//first we shorten the ip's
				$ip1 = inet_ntop( inet_pton( $ip1 ) );
				$ip2 = inet_ntop( inet_pton( $ip2 ) );

				//we convert ip1 and ip2 to binary
				$ipbin1 = self::IPv62Binary( $ip1 );
				$ipbin2 = self::IPv62Binary( $ip2 );

				$prefixLength = 0;

				while ( $prefixLength < 128 && $ipbin1[ $prefixLength ] === $ipbin2[ $prefixLength ] ) {
					$prefixLength ++;
				}

				return $ip1 . '/' . $prefixLength;

			} else {
				$ip1Long = ip2long( $ip1 );
				$ip2Long = ip2long( $ip2 );

				// Calculate the mask
				$mask     = $ip1Long ^ $ip2Long;
				$maskBits = 32 - floor( log( $mask + 1, 2 ) );

				return long2ip( $ip1Long ) . '/' . $maskBits;
			}
		}

		public static function calculate_number_of_ips_form_cidr( $cidr ) {
			//first we determine v4 or v6
			if ( strpos( $cidr, ':' ) !== false ) {
				//v6
				$cidr = explode( '/', $cidr );
				$cidr = $cidr[1];
				$ips  = pow( 2, ( 128 - $cidr ) );

				return self::formatNumber( $ips );
			}
			//we calculate the number of ips in a cidr
			$cidr = explode( '/', $cidr );
			$cidr = $cidr[1];
			$ips  = pow( 2, ( 32 - $cidr ) );

			return self::formatNumber( $ips );
		}

		public static function formatNumber( $number ) {
			$suffixes = array(
				'',
				'k (Thousand)',
				'M (Million)',
				'B (Billion)',
				'T (Trillion)',
				'P (Quadrillion)',
				'E (Quintillion)',
				'Z (Sextillion)',
				'Y (Septillion)'
			);
			$i        = 0;
			while ( abs( $number ) >= 1000 && $i < count( $suffixes ) - 1 ) {
				$number /= 1000;
				$i ++;
			}

			return round( $number, 2 ) . $suffixes[ $i ];
		}

		/**
		 * Fetches a list of IP addresses in the allowlist.
		 *
		 * This method retrieves a list of IP addresses from the "rsssl_login_attempts" table
		 * based on certain conditions and filters set in the method.
		 *
		 * If the LoginAttempt is not activated, it will fetch dummy data. It uses the
		 * `rsssl_dataTable` and `QueryBuilder` objects to structure and execute the SQL query.
		 *
		 * @param  array|null  $data  An optional array of data that might contain filters,
		 *                         search criteria, pagination, or sorting instructions for
		 *                         the rsssl_dataTable.
		 *
		 * @return array The resulting list of IP addresses in the allowlist and any associated data.
		 *               The result structure is not only limited to the IP addresses but also
		 *               contains metadata and other relevant information. It also appends the $data
		 *               parameter to the 'post' key of the result for tracing purposes.
		 *
		 * @throws Exception Throws an exception if there's an error while fetching the data.
		 *
		 * @uses rsssl_dataTable To structure the SQL query.
		 * @uses QueryBuilder To build the actual SQL query.
		 *
		 * @example
		 * $exampleClass = new ClassName();
		 * $ipAllowList = $exampleClass->get_list();
		 * foreach ($ipAllowList as $ip) {
		 *     echo $ip['source_ip'];
		 * }
		 *
		 * @since 7.0.0
		 * @author Marcel Santing
		 */
		public function get_list( $data = null ) {
			if ( ! LoginAttempt::activated() ) {
				return $this->get_dummy_data( $data, 'source_ip' );
			}
			global $wpdb;
			try {
				//manual ad a filter value to the $data
				$dataTable = new rsssl_dataTable( $data, new QueryBuilder( $wpdb->prefix . 'rsssl_login_attempts' ) );
				$dataTable->set_select_columns( [
					"id",
					"attempt_value",
					"attempt_type",
					"status",
					"last_failed",
					"raw:DATE_FORMAT(FROM_UNIXTIME(last_failed), '%%H:%%i, %%M %%e') as datetime",
				] );
				//we already add a where selection in the query builder
				$dataTable->set_where( [
					"attempt_type",
					"=",
					"source_ip",
				] );

				$result         = $dataTable
					->validate_search()
					->validate_sorting()
					->validate_filter()
					->validate_pagination()
					->get_results();
				$result['post'] = $data;

				return $result;

			} catch ( Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}


		/**
		 * Generates Dummy Data for the datatable and is used when the LoginAttempt is not activated
		 *
		 * @param $data  //the data from the datatable ajax request
		 * @param $prefilter  //the prefilter value
		 *
		 * @return array
		 * @throws Exception
		 */
		public function get_dummy_data( $data, $prefilter = null ): array {
			$result = [
				[
					"id"            => 1,
					"attempt_value" => "johnDoe", // example username
					"attempt_type"  => "username",
					"status"        => "blocked",
					"last_failed"   => 1630000000,
					"datetime"      => "14:00, Aug 17",
				],
				[
					"id"            => 2,
					"attempt_value" => "john.doe@example.com", // example email
					"attempt_type"  => "username",
					"status"        => "blocked",
					"last_failed"   => 1630005000,
					"datetime"      => "15:00, Aug 17",
				],
				[
					"id"            => 3,
					"attempt_value" => "192.168.1.1", // example ipv4
					"attempt_type"  => "source_ip",
					"status"        => "blocked",
					"last_failed"   => 1630010000,
					"datetime"      => "16:00, Aug 17",
				],
				[
					"id"            => 4,
					"attempt_value" => "2001:0db8:85a3:0000:0000:8a2e:0370:7334", // example ipv6
					"attempt_type"  => "source_ip",
					"status"        => "blocked",
					"last_failed"   => 1630015000,
					"datetime"      => "17:00, Aug 17",
				],
				[
					"id"            => 5,
					"attempt_value" => "USA", // example country
					"attempt_type"  => "country",
					"status"        => "blocked",
					"last_failed"   => 1630020000,
					"datetime"      => "18:00, Aug 17",
				],
				[
					"id"            => 6,
					"attempt_value" => "EU", // example country
					"attempt_type"  => "region",
					"status"        => "blocked",
					"last_failed"   => 1630025000,
					"datetime"      => "19:00, Aug 17",
				],
			];

			$dataTable = new rsssl_dataTable( $result, new ArrayQueryBuilder( $result ) );

			$result = $dataTable->set_where( [
				"attempt_type",
				"=",
				$prefilter,
			] )
			                    ->validate_search()
			                    ->validate_sorting()
			                    ->validate_filter()
			                    ->validate_pagination()
			                    ->get_results();

			return $result;
		}


		/**
		 *
		 * @throws Exception
		 */
		public function get_user_list( $data ) {
			global $wpdb;
			if ( ! LoginAttempt::activated() ) {
				return $this->get_dummy_data( $data, 'username' );
			}

			try {
				//manual ad a filter value to the $data
				$dataTable = new rsssl_dataTable( $data, new QueryBuilder( $wpdb->prefix . 'rsssl_login_attempts' ) );
				$dataTable->set_select_columns( [
					"id",
					"attempt_value",
					"attempt_type",
					"status",
					"last_failed",
					"raw:DATE_FORMAT(FROM_UNIXTIME(last_failed), '%%H:%%i, %%M %%e') as datetime",
				] );
				//we already add a where selection in the query builder
				$dataTable->set_where( [
					"attempt_type",
					"=",
					"username",
				] );


				$result1         = $dataTable
					->validate_search()
					->validate_sorting()
					->validate_filter()
					->validate_pagination()
					->get_results();
				$result1['post'] = $data;

				return $result1;

			} catch ( Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}


		/**
		 * @throws Exception
		 */
		public function get_country_list( $data ) {
			if ( ! LoginAttempt::activated() ) {
				return $this->get_dummy_data( $data, 'country' );
			}

			//since countries has a status all option we need to call a different function.
			if ( isset( $data['filterValue'] ) && $data['filterValue'] === 'countries' ) {
				return ( new GeoLocation() )->get_countries( $data );
			} elseif ( isset( $data['filterValue'] ) && $data['filterValue'] === 'regions' ) {
				return ( new GeoLocation() )->get_regions( $data );
			}

			global $wpdb;

			try {
				//manual ad a filter value to the $data
				$dataTable = new rsssl_dataTable( $data, new QueryBuilder( $wpdb->prefix . 'rsssl_login_attempts' ) );
				$dataTable->set_select_columns( [
					"attempt_value",
					"raw: " . $wpdb->prefix . "rsssl_login_attempts.id as id",
					"attempt_type",
					"raw: c.country_name as country_name",
					"raw: c.region as region",
					"raw: " . $wpdb->prefix . "rsssl_login_attempts.status as status",
					"last_failed",
					"raw:DATE_FORMAT(FROM_UNIXTIME(last_failed), '%%H:%%i, %%M %%e') as datetime",
				] );

				$dataTable->join( $wpdb->prefix . 'rsssl_country' )
				          ->on( $wpdb->prefix . 'rsssl_login_attempts.attempt_value', '=', 'c.iso2_code' )
				          ->as( 'c' );

				//we already add a where selection in the query builder
				//	$dataTable->set_where_in( "attempt_type", [ "country", "region" ] );

				$result2         = $dataTable
					->validate_search()
					->validate_sorting()
					->validate_filter()
					->validate_pagination()
					->get_results();
				$result2['post'] = $data;

				return $result2;

			} catch ( Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}

		/**
		 * @throws Exception
		 */
		public function update_row( $data, $type = 'ip' ): array {
			global $wpdb;
			$result = $wpdb->update(
				$wpdb->prefix . 'rsssl_login_attempts',
				[
					'status' => $data['status'],
				],
				[
					'id' => $data['id'],
				],
				[ '%s' ],
				[ '%d' ]
			);

			if ( $result === false ) {
				return [ 'error', $wpdb->last_error, $wpdb->last_query ];
			}

			//now we fetch the record
			$result = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE id = {$data['id']}",
				ARRAY_A );
			//no errors so we add an event log based on the status
			$this->logEventData( $result );

			return [ 'success', $data ];

		}

		public function add_to_ip_list( $data ) {
			global $wpdb;
			$result = $wpdb->insert(
				$wpdb->prefix . 'rsssl_login_attempts',
				[
					'attempt_value' => $data['ipAddress'],
					'attempt_type'  => 'source_ip',
					'status'        => $data['status'],
					'last_failed'   => time(),
				],
				[ '%s', '%s', '%s', '%s', '%d' ]
			);

			if ( $result === false ) {
				return [ 'error', $wpdb->last_error, $wpdb->last_query ];
			}

			$this->logEventData( $data );

			return [ 'success', $data, $wpdb->last_query ];
		}

		/**
		 * @throws Exception
		 */
		public function add_country_to_list( $data ): array {
			global $wpdb;
			//first we check if the country is already in the list
			// Check if the country already exists using a prepared statement
			$sql    = $wpdb->prepare(
				"SELECT COUNT(*) FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_value = %s AND attempt_type = 'country'",
				$data['country']
			);
			$exists = $wpdb->get_var( $sql );

			if ( $exists ) {
				return [ 'error', 'Country already exists in the list.' ];
			}

			$result = $wpdb->insert(
				$wpdb->prefix . 'rsssl_login_attempts',
				[
					'attempt_value' => $data['country'],
					'attempt_type'  => 'country',
					'status'        => $data['status'],
					'last_failed'   => time(),
				],
				[ '%s', '%s', '%s', '%s', '%d' ]
			);

			if ( $result === false ) {
				return [ 'error', $wpdb->last_error, $wpdb->last_query ];
			}

			//we fetch the datarecord
			$result = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_value = '{$data['country']}' AND attempt_type = 'country'",
				ARRAY_A );
			$this->logEventData( $result );

			return [ 'success', $data, $wpdb->last_query ];
		}

		/**
		 * @throws Exception
		 */
		public function remove_country_from_list( $data ) {
			global $wpdb;
			//we fetch the data-record
			$record = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_value = '{$data['country']}' AND attempt_type = 'country'",
				ARRAY_A );

			$result = $wpdb->delete(
				$wpdb->prefix . 'rsssl_login_attempts',
				[
					'attempt_value' => $data['country'],
					'attempt_type'  => 'country',
				],
				[ '%s', '%s' ]
			);

			if ( $result === false ) {
				return [ 'error', $wpdb->last_error, $wpdb->last_query ];
			}

			//we fetch the datarecord
			$result = $wpdb->get_row( "SELECT * FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_value = '{$data['country']}' AND attempt_type = 'country'",
				ARRAY_A );
			$this->logEventData( $record, true );

			return [ 'success', $data, $wpdb->last_query ];
		}

		/**
		 * @throws Exception
		 */
		public function add_region_to_list( $data ): array {
			global $wpdb;

			//based on the region we need to get the countries associated with it
			$countries = $wpdb->get_results( "SELECT iso2_code FROM " . $wpdb->prefix . "rsssl_country WHERE region_code = '{$data['region']}'" );

			//now we add the countries to the list
			foreach ( $countries as $country ) {
				//we add a status property
				$country->status = $data['status'];
				$country->attempt_type = 'country';
				$country->attempt_value = $country->iso2_code;

				$sql    = $wpdb->prepare(
					"SELECT COUNT(*) FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_value = %s AND attempt_type = 'country'",
					$country->iso2_code
				);
				$exists = $wpdb->get_var( $sql );

				if ( $exists ) {
					continue;
				}

				$result = $wpdb->insert(
					$wpdb->prefix . 'rsssl_login_attempts',
					[
						'attempt_value' => $country->iso2_code,
						'attempt_type'  => 'country',
						'status'        => $data['status'],
						'last_failed'   => time(),
					],
					[ '%s', '%s', '%s', '%s', '%d' ]
				);

				if ( $result === false ) {
					return [ 'error', $wpdb->last_error, $wpdb->last_query ];
				}
			}

			$this->logEventData( $countries );

			return [ 'success', $data, $wpdb->last_query ];
		}


		public function add_user_to_list( $data ): array {
			global $wpdb;
			$result = $wpdb->insert(
				$wpdb->prefix . 'rsssl_login_attempts',
				[
					'attempt_value' => $data['user'],
					'attempt_type'  => 'username',
					'status'        => $data['status'],
					'last_failed'   => time(),
				],
				[ '%s', '%s', '%s', '%s', '%d' ]
			);

			if ( $result === false ) {
				return [ 'error', $wpdb->last_error, $wpdb->last_query ];
			}

			return [ 'success', $data ];
		}

		public function update_multi_rows( $data ) {
			global $wpdb;
			$ids = implode( ',', $data['ids'] );
			$wpdb->query( "UPDATE " . $wpdb->prefix . "rsssl_login_attempts SET status = '{$data['status']}' WHERE id IN ({$ids})" );

			//now we fetch the record
			$result = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE id IN ({$ids})",
				ARRAY_A );
			//no errors so we add an event log based on the status
			$this->logEventData( $result );

			return [ 'success', $data ];
		}

		/**
		 * @throws Exception
		 */
		public function delete_entry( $data ) {
			global $wpdb;

			// Safely prepare the SQL statement for fetching the record
			$prepared_query = $wpdb->prepare( "SELECT id, status, attempt_type, attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE id = %d",
				$data['id'] );
			$result         = $wpdb->get_row( $prepared_query, ARRAY_A );

			// Check if record exists
			if ( ! $result ) {
				return [ 'error', 'Record not found.' ];
			}

			// Safely delete the record
			$wpdb->delete( $wpdb->prefix . 'rsssl_login_attempts', [ 'id' => $data['id'] ], [ '%d' ] );

			// Log the event based on record status
			$this->logEventData( $result );

			return [ 'success', $data ];
		}

		/**
		 * @throws Exception
		 */
		public function delete_multi_entries( $data ): array {
			global $wpdb;

			// Safely prepare the SQL statement for deletion
			$ids            = implode( ',', array_map( 'intval', $data['ids'] ) ); // Ensure ids are integers
			$prepared_query = $wpdb->prepare( "SELECT id, status, attempt_type, attempt_value FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE id IN ($ids)" );
			$deletedRecords = $wpdb->get_results( $prepared_query );

			// Now safely delete the records
			$delete_query  = $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE id IN ($ids)" );
			$deleted_count = $wpdb->query( $delete_query );

			// Check if records were actually deleted
			if ( $deleted_count === false ) {
				return [ 'error', 'Failed to delete records.' ];
			}

			// Log events
			$this->logEventData( $deletedRecords );

			return [ 'success', $data ];
		}

		/**
		 * Logs events for the given records.
		 *
		 * @param $data
		 * @param  bool  $deleted
		 *
		 * @return void
		 * @throws Exception
		 */
		private function logEventData( $data, $deleted = false ) {
			// If data is not an array, convert it to an array
			if ( ! is_array( $data ) || ( is_object( $original = $data ) && $data === (array) $original ) ) {
				$data = [ $data ];
			}

			if ( ! isset( $data[0] ) && isset( $data['id'] ) ) {
				$data = [ $data ];
			}


			foreach ( $data as $record ) {
				if (is_array($record)) {
					$status = $record['status'] ?? null;
					$attempt_type = $record['attempt_type'] ?? null;
					$attempt_value = $record['attempt_value'] ?? null;
				} else if (is_object($record)) {
					$status = $record->status ?? null;
					$attempt_type = $record->attempt_type ?? null;
					$attempt_value = $record->attempt_value ?? null;
				} else {
					// If it's neither an array nor an object, skip this iteration
					continue;
				}

				switch ( [ $status, $attempt_type ] ) {
					case [ 'blocked', 'source_ip' ]:
						$event_type = self::EVENT_CODE_IP_BLOCKED;
						if ( $deleted ) {
							$event_type = self::EVENT_CODE_IP_UNBLOCKED;
						}
						$event_type = EventType::add_to_block( $event_type, $attempt_value );
						break;
					case [ 'allowed', 'source_ip' ]:
						$event_type = self::EVENT_CODE_IP_ADDED_TO_ALLOWLIST;
						if ( $deleted ) {
							$event_type = self::EVENT_CODE_IP_REMOVED_FROM_ALLOWLIST;
						}
						$event_type = EventType::add_to_block( $event_type, $attempt_value );
						break;
					case [ 'blocked', 'username' ]:
						$event_type = self::EVENT_CODE_USER_BLOCKED;
						if ( $deleted ) {
							$event_type = self::EVENT_CODE_USER_UNBLOCKED;
						}
						$event_type = EventType::add_to_block( $event_type, '', $attempt_value );
						break;
					case [ 'allowed', 'username' ]:
						$event_type = self::EVENT_CODE_USER_ADDED_TO_ALLOWLIST;
						if ( $deleted ) {
							$event_type = self::EVENT_CODE_USER_REMOVED_FROM_ALLOWLIST;
						}
						$event_type = EventType::add_to_block( $event_type, '', $attempt_value );
						break;
					case [ 'locked', 'username' ]:
						$event_type = self::EVENT_CODE_USER_LOCKED;
						if ( $deleted ) {
							$event_type = self::EVENT_CODE_USER_UNLOCKED;
						}
						$event_type = EventType::add_to_block( $event_type, '', $attempt_value );
						break;
					case [ 'locked', 'source_ip' ]:
						$event_type = self::EVENT_CODE_IP_LOCKED;
						if ( $deleted ) {
							$event_type = self::EVENT_CODE_IP_UNLOCKED;
						}
						$event_type = EventType::add_to_block( $event_type, $attempt_value );
						break;
					case [ 'blocked', 'country' ]:
						$event_type = self::EVENT_CODE_COUNTRY_BLOCKED;
						if ( $deleted ) {
							$event_type = self::EVENT_CODE_COUNTRY_UNBLOCKED;
						}
						$event_type = EventType::add_to_block( $event_type, '', '', $attempt_value );
						break;
					default:
						// No event to log for this record
						continue 2;
				}

				rsssl_event_log::log_event( $event_type );
			}
		}

		public function remove_region_from_list( $data ) {
			global $wpdb;
			//based on the region we need to get the countries associated with it
			$countries = $wpdb->get_results( "SELECT iso2_code FROM " . $wpdb->prefix . "rsssl_country WHERE region_code = '{$data['region']}'" );

			//now we add the countries to the list
			foreach ( $countries as $country ) {
				$sql    = $wpdb->prepare(
					"SELECT COUNT(*) FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_value = %s AND attempt_type = 'country'",
					$country->iso2_code
				);

				//we add a status property
				$country->status = $data['status'];
				$country->attempt_type = 'country';
				$country->attempt_value = $country->iso2_code;

				$exists = $wpdb->get_var( $sql );

				if ( ! $exists ) {
					continue;
				}

				$result = $wpdb->delete(
					$wpdb->prefix . 'rsssl_login_attempts',
					[
						'attempt_value' => $country->iso2_code,
						'attempt_type'  => 'country',
					],
					[ '%s', '%s' ]
				);

				if ( $result === false ) {
					return [ 'error', $wpdb->last_error, $wpdb->last_query ];
				}
			}

			$this->logEventData( $countries, true );

			return [ 'success', $data, $wpdb->last_query ];
		}

		/**
		 * @throws Exception
		 */
		public function add_countries_to_list( $data ) {
			global $wpdb;

			$countries = [];
			//now we add the countries to the list
			foreach ( $data['countries'] as $country ) {

				//we create an object
				$countryAdded = new StdClass();
				$countryAdded->iso2_code = $country;
				$countryAdded->status = 'blocked';
				$countryAdded->attempt_type = 'country';
				$countryAdded->attempt_value = $country;

				$countries[] = $countryAdded;

				$sql    = $wpdb->prepare(
					"SELECT COUNT(*) FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_value = %s AND attempt_type = 'country'",
					$country
				);

				$exists = $wpdb->get_var( $sql );

				if ( $exists ) {
					continue;
				}

				$result = $wpdb->insert(
					$wpdb->prefix . 'rsssl_login_attempts',
					[
						'attempt_value' => $country,
						'attempt_type'  => 'country',
						'status'        => $data['status'],
						'last_failed'   => time(),
					],
					[ '%s', '%s', '%s', '%s', '%d' ]
				);

				if ( $result === false ) {
					return [ 'error', $wpdb->last_error, $wpdb->last_query ];
				}
			}

			$this->logEventData( $countries );

			return [ 'success', $data, $wpdb->last_query ];
		}

		public function remove_countries_from_list( $data ) {
			global $wpdb;

			$countries = [];
			//now we add the countries to the list
			foreach ( $data['countries'] as $country ) {

				//we create an object
				$countryAdded = new StdClass();
				$countryAdded->iso2_code = $country;
				$countryAdded->status = 'blocked';
				$countryAdded->attempt_type = 'country';
				$countryAdded->attempt_value = $country;

				$countries[] = $countryAdded;

				$sql    = $wpdb->prepare(
					"SELECT COUNT(*) FROM " . $wpdb->prefix . "rsssl_login_attempts WHERE attempt_value = %s AND attempt_type = 'country'",
					$country
				);

				$exists = $wpdb->get_var( $sql );

				if ( ! $exists ) {
					continue;
				}

				$result = $wpdb->delete(
					$wpdb->prefix . 'rsssl_login_attempts',
					[
						'attempt_value' => $country,
						'attempt_type'  => 'country',
					],
					[ '%s', '%s' ]
				);

				if ( $result === false ) {
					return [ 'error', $wpdb->last_error, $wpdb->last_query ];
				}
			}

			$this->logEventData( $countries, true );

			return [ 'success', $data, $wpdb->last_query ];
		}
	}

	new Rsssl_Limit_Login_Attempts();

	if ( ! function_exists( 'RSSLPRO\Security\wordpress\rsssl_ip_list_api' ) ) {
		/**
		 * @throws Exception
		 */
		function rsssl_ip_list_api( array $response, string $action, $data ) {
			if ( ! rsssl_user_can_manage() ) {
				return $response;
			}
			switch ( $action ) {
				case 'ip_list':
					//creating a random string based on time.
					$response = ( new Rsssl_Limit_Login_Attempts() )->get_list( $data );
					break;
				case 'user_list':
					$response = ( new Rsssl_Limit_Login_Attempts() )->get_user_list( $data );
					break;
				case 'ip_update_row':
					$response = ( new Rsssl_Limit_Login_Attempts() )->update_row( $data );
					break;
				case 'user_update_multi_row':
				case 'update_multi_row':
				case 'ip_update_multi_row':
					$response = ( new Rsssl_Limit_Login_Attempts() )->update_multi_rows( $data );
					break;
				case 'ip_add_ip_address':
					$response = ( new Rsssl_Limit_Login_Attempts() )->add_to_ip_list( $data );
					break;
				case 'user_add_user':
					$response = ( new Rsssl_Limit_Login_Attempts() )->add_user_to_list( $data );
					break;
				case 'user_update_row':
					$response = ( new Rsssl_Limit_Login_Attempts() )->update_row( $data, 'username' );
					break;
				case 'get_mask_from_range':
					$cidr     = Rsssl_Limit_Login_Attempts::calculate_cidr_from_range( $data['lowest'],
						$data['highest'] );
					$ip_count = Rsssl_Limit_Login_Attempts::calculate_number_of_ips_form_cidr( $cidr );
					$response = [ 'cidr' => $cidr, 'ip_count' => $ip_count ];
					break;
				case 'add_country_to_list':
					$response = ( new Rsssl_Limit_Login_Attempts() )->add_country_to_list( $data );
					break;
				case 'add_countries_to_list':
					$response = ( new Rsssl_Limit_Login_Attempts() )->add_countries_to_list( $data );
					break;
					case 'remove_countries_from_list':
					$response = ( new Rsssl_Limit_Login_Attempts() )->remove_countries_from_list( $data );
					break;
				case 'remove_country_from_list':
					$response = ( new Rsssl_Limit_Login_Attempts() )->remove_country_from_list( $data );
					break;
				case 'add_region_to_list':
					$response = ( new Rsssl_Limit_Login_Attempts() )->add_region_to_list( $data );
					break;
				case 'remove_region_from_list':
					$response = ( new Rsssl_Limit_Login_Attempts() )->remove_region_from_list( $data );
					break;
				case 'delete_entry':
					$response = ( new Rsssl_Limit_Login_Attempts() )->delete_entry( $data );
					break;
				case 'delete_multi_entries':
					$response = ( new Rsssl_Limit_Login_Attempts() )->delete_multi_entries( $data );
					break;
				case 'country_list':
					//creating a random string based on time.
					$response = ( new Rsssl_Limit_Login_Attempts() )->get_country_list( $data );
					break;
				default:
					break;
			}

			return $response;
		}

		// Add the rsssl_ip_list_api function as a filter callback
		add_filter( 'rsssl_do_action', 'RSSLPRO\Security\wordpress\rsssl_ip_list_api', 10, 3 );
	}
}