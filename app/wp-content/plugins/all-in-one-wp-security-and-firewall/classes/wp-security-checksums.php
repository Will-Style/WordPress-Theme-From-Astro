<?php

if (!defined('ABSPATH')) die('No direct access allowed');

/**
 * Class to handle file checksum related operations
 */
class AIOWPSecurity_Checksums {

	protected static $_instance = null;

	private static $core_endpoint = 'https://api.wordpress.org/core/checksums/1.0/';

	private static $core_endpoint_timeout = 15;

	private static $plugin_endpoint = 'https://downloads.wordpress.org/plugin-checksums/';

	private static $plugin_endpoint_timeout = 15;

	private static $plugin_checksums_ttl = 86400; // 24 hours

	private static $checksums = array();

	/**
	 * This method will create and return the only instance of this class.
	 *
	 * @return AIOWPSecurity_Checksums Returns an instance of the class
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor for the class.
	 */
	private function __construct() {
		// Add actions here in future
	}

	/**
	 * This function will generate the md5 and sha256 hash for the passed in file
	 *
	 * @param string $filename - the name of the file
	 *
	 * @return array - an array with the md5 and sha256 hash or an empty array if the file does not exist or is unreadable
	 */
	public static function generate_file_checksums($filename) {
		if (!file_exists($filename) || !is_readable($filename)) return array();

		return array(
			'md5'    => hash_file('md5', $filename),
			'sha256' => hash_file('sha256', $filename),
		);
	}

	/**
	 * This function will verify the checksum of the passed in file
	 *
	 * @param string $filename - the file name
	 *
	 * @return array - returns an array with checksum information for the passed in file
	 */
	public static function verify_file_checksums($filename) {

		$checksum_result = array(
			'success' => true,
			'checksums' => array(
				'known_checksums' => array(),
				'file_checksums' => array(),
			),
		);

		$known_checksums = self::lookup_file_checksum($filename);

		// If we don't have a checksum for this file return true
		if (empty($known_checksums)) {
			return $checksum_result;
		}

		$file_checksums = self::generate_file_checksums($filename);

		// We either couldn't read the file or it no longer exists
		if (empty($file_checksums)) {
			return $checksum_result;
		}

		$checksum_result['checksums']['known_checksums'] = $known_checksums;
		$checksum_result['checksums']['file_checksums'] = $file_checksums;

		foreach ($known_checksums as $algo => $known_checksum) {
			if (!isset($file_checksums[$algo])) continue;
			
			// Sometimes known checksum is an array e.g. more than 1 "correct" value.  Probably because you can update any file without bumping the plugin version.
			$known_checksum = (array) $known_checksum;
			$match_found = false;
			foreach ($known_checksum as $expected) {
				if (hash_equals($expected, $file_checksums[$algo])) {
					$match_found = true;
					break;
				}
			}
			if (!$match_found) {
				$checksum_result['success'] = false;
				break;
			}
		}
		
		return $checksum_result;
	}

	/**
	 * This function will lookup the file checksums for the passed in file name
	 *
	 * @param string $filename - the file name
	 *
	 * @return array|boolean - returns false if the checksum is not found other wise the checksum arrays
	 */
	private static function lookup_file_checksum($filename) {
		$checksums = self::get_checksums();

		$filename = wp_normalize_path($filename);
		$is_core_file = str_replace(wp_normalize_path(ABSPATH), '', $filename);

		if (isset($checksums['core']['files'][$is_core_file])) return $checksums['core']['files'][$is_core_file];

		$filename_parts = explode('/', ltrim(str_replace(wp_normalize_path(WP_PLUGIN_DIR), '', $filename), '/'), 2);
		if (2 !== count($filename_parts)) return false;
		
		list($plugin, $plugin_file) = $filename_parts;
		if (isset($checksums['plugins'][$plugin])) {
			$plugin_data = get_plugin_data(wp_normalize_path(trailingslashit(WP_PLUGIN_DIR)) . $checksums['plugins'][$plugin]['plugin_file']);
			$version = $plugin_data['Version'];

			if (isset($checksums['plugins'][$plugin]['versions'][$version][$plugin_file])) return $checksums['plugins'][$plugin]['versions'][$version][$plugin_file];
		}

		return false;
	}

	/**
	 * This function will return the checksums array
	 *
	 * @return array - that contains all the checksums
	 */
	public static function get_checksums() {
		return self::$checksums;
	}

	/**
	 * This function will update the checksums array
	 *
	 * @return void
	 */
	public static function update_checksums() {
		$checksums = array();
		$core_checksums = self::update_core_checksums();
		$checksums['core'] = $core_checksums;
		$plugin_checksums = self::update_plugin_checksums();
		$checksums['plugins'] = $plugin_checksums;

		self::$checksums = $checksums;
	}

	/**
	 * This function will update the core checksums merging the saved checksums with any new checksums found
	 *
	 * @return array - an array of core checksums
	 */
	private static function update_core_checksums() {
		global $aio_wp_security, $wp_version;

		$core_checksums = get_option('aiowps_core_checksums', array());

		if (isset($core_checksums[$wp_version])) return $core_checksums[$wp_version];

		$current_version_checksums = self::download_core_checksums($wp_version);
		
		if (is_wp_error($current_version_checksums)) {
			$aio_wp_security->debug_logger->log_debug("AIOWPSecurity_Checksums::update_core_checksums() failed: {$current_version_checksums->get_error_message()}", 4);
			return array('files' => array());
		}

		if (count($core_checksums) > 2) {
			$core_checksums = array_slice($core_checksums, -2, 2, true);
		}

		$core_checksums[$wp_version] = $current_version_checksums;

		update_option('aiowps_core_checksums', $core_checksums);

		return $current_version_checksums;
	}

	/**
	 * This function will return an array of MD5 hashes for the currently installed WordPress version and locale
	 *
	 * @param string $wp_version - the WordPress version
	 *
	 * @return array|WP_Error - returns an array of checksums or a WP_Error if something went wrong
	 */
	private static function download_core_checksums($wp_version) {

		$wp_locale = get_locale();

		$url = self::$core_endpoint . '?version=' . $wp_version . '&locale=' . $wp_locale;
		$options = array(
			'timeout' => self::$core_endpoint_timeout,
		);
	
		$response = wp_remote_get($url, $options);
	
		if (is_wp_error($response)) return $response;

		$response_code = wp_remote_retrieve_response_code($response);
		
		if (200 !== $response_code) return new WP_Error("unexpected_response_code", "$response_code returned from core checksums request");

		$body = trim(wp_remote_retrieve_body($response));
		$body = json_decode($body, true);
	
		if (!is_array($body) || !isset($body['checksums']) || !is_array($body['checksums'])) {
			return new WP_Error("unexpected_response", "The returned core checksums response did not contain the expected data.");
		}

		// Convert the checksums response to a standard format
		$checksums = array(
			'files' => array()
		);

		foreach ($body['checksums'] as $file => $md5) {
			$checksums['files'][$file] = array('md5' => $md5);
		}
	
		return $checksums;
	}

	/**
	 * This function will update the plugin checksums, using cached data where available.
	 *
	 * Checksums are cached in the options table per slug+version with a TTL of
	 * $plugin_checksums_ttl seconds. This avoids redundant downloads when a long-running
	 * scan is interrupted and resumed on slow or unreliable hosts.
	 *
	 * @return array - an array of plugin checksums
	 */
	private static function update_plugin_checksums() {

		$plugin_checksums = array();
		$cache = get_option('aiowps_plugin_checksums', array());
		$cache_updated = false;
		$now = time();

		if (!function_exists('get_plugins')) include_once(ABSPATH.'wp-admin/includes/plugin.php');
		$plugins = get_plugins();

		foreach ($plugins as $plugin_file => $plugin_data) {
			$slug = dirname(plugin_basename($plugin_file));
			$version = $plugin_data['Version'];
			$cache_key = $slug . '|' . $version;

			if (isset($cache[$cache_key]) && ($now - $cache[$cache_key]['downloaded_at']) < self::$plugin_checksums_ttl) {
				$plugin_checksums[$slug] = $cache[$cache_key]['data'];
				continue;
			}

			$downloaded = self::download_plugin_checksums_for_plugin($slug, $version, $plugin_file);

			if (null === $downloaded) continue;

			$plugin_checksums[$slug] = $downloaded;
			$cache[$cache_key] = array(
				'downloaded_at' => $now,
				'data'          => $downloaded,
			);
			$cache_updated = true;
		}

		if ($cache_updated) {
			update_option('aiowps_plugin_checksums', $cache);
		}

		return $plugin_checksums;
	}

	/**
	 * This function will return an array of MD5/sha256 hashes for a single plugin version.
	 * Returns null on any error so the caller can skip the plugin.
	 *
	 * @param string $slug        - plugin slug
	 * @param string $version     - plugin version
	 * @param string $plugin_file - plugin file path (for storing in result)
	 *
	 * @return array|null
	 */
	private static function download_plugin_checksums_for_plugin($slug, $version, $plugin_file) {
		global $aio_wp_security;

		$url = self::$plugin_endpoint . trailingslashit($slug) . $version . '.json';
		$options = array(
			'timeout' => self::$plugin_endpoint_timeout,
		);

		$response = wp_remote_get($url, $options);

		if (is_wp_error($response)) {
			$aio_wp_security->debug_logger->log_debug("wp_remote_get() failed for plugin checksums URL: $url, error: {$response->get_error_message()}", 4);
			return null;
		}

		$response_code = wp_remote_retrieve_response_code($response);

		if (200 !== $response_code) {
			if (404 !== $response_code) {
				$aio_wp_security->debug_logger->log_debug("$response_code returned from plugin checksums request URL: $url", 4);
			}
			return null;
		}

		$body = trim(wp_remote_retrieve_body($response));
		$body = json_decode($body, true);

		if (!is_array($body) || !isset($body['plugin']) || !isset($body['version']) || !isset($body['files']) || !is_array($body['files'])) {
			$aio_wp_security->debug_logger->log_debug("The returned plugin checksums response did not contain the expected data.", 4);
			return null;
		}

		return array(
			'plugin_file' => $plugin_file,
			'versions'    => array(
				$body['version'] => $body['files']
			),
		);
	}
}

AIOWPSecurity_Checksums::instance();
