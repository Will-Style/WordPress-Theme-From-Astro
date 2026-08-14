<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (!trait_exists('AIOWPSecurity_Singleton_Trait')) require_once(AIO_WP_SECURITY_PATH . '/classes/traits/wp-security-singleton-trait.php');

if (class_exists('AIOWPSecurity_Heartbeat')) return;

class AIOWPSecurity_Heartbeat {


	use AIOWPSecurity_Singleton_Trait;
	
	/**
	 * Nonce action used to protect AIOS heartbeat commands.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'aios-heartbeat-nonce';

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	private function __construct() {
		global $pagenow;

		$pages_enabled = array('aiowpsec_filescan');
		$pages_enabled = apply_filters('aiowps_heartbeat_allowed_pages', $pages_enabled);

		// only for the pages that we enable in `$pages_enabled`
		if (isset($_GET['page'])) {
			$query_page = sanitize_text_field(wp_unslash($_GET['page'])); // phpcs:ignore WordPress.Security.NonceVerification -- PCP warning. Processing form data without nonce verification. No nonce.
		} else {
			$query_page = $pagenow;
		}

		if (in_array($query_page, $pages_enabled, true)) {
			add_filter('heartbeat_settings', array($this, 'set_heartbeat_time_interval'), PHP_INT_MAX);
		}

		// Handle heartbeat events
		add_filter('heartbeat_received', array($this, 'receive_heartbeat'), 10, 2);
	}

	/**
	 * Set custom heartbeat API interval
	 *
	 * @param array $settings Current WP settings
	 * @return array
	 */
	public function set_heartbeat_time_interval($settings) {
		$settings['interval'] = 10;
		return $settings;
	}

	/**
	 * Receive Heartbeat data and respond.
	 *
	 * Processes data received via a Heartbeat request, and returns additional data to pass back to the front end.
	 *
	 * @param array $response Heartbeat response data to pass back to front end.
	 * @param array $data     Data received from the front end (unslashed).
	 *
	 * @return array
	 */
	public function receive_heartbeat($response, $data) {

		$nonce = (isset($data['aios_heartbeat_nonce']) && is_string($data['aios_heartbeat_nonce'])) ? sanitize_text_field($data['aios_heartbeat_nonce']) : '';

		if (true !== AIOWPSecurity_Utility_Permissions::check_nonce_and_user_cap($nonce, self::NONCE_ACTION)) {
					return $response;
		}

		$commands = new AIOWPSecurity_Commands();

		foreach ($data as $uid => $command) {

			if (!$this->is_aios_heartbeat($uid)) {
				continue;
			}
			
			$command_name = key($command);

			if ('aios_file_scan_task' == $command_name) {
				$command_data = current($command);
				$command_data_param = isset($command_data['data']) ? $command_data['data'] : null;
				
				$allowed = $commands::get_allowed_heartbeat_commands();
				$subaction = isset($command_data['subaction']) ? $command_data['subaction'] : '';

				if (!in_array($subaction, $allowed, true) || !is_callable(array($commands, $subaction))) {
					continue;
				}

				$params = is_array($command_data_param) ? array($command_data_param) : array();
				$command_response = call_user_func_array(array($commands, $subaction), $params);
				$response['callbacks'][$uid] = $command_response;
			}
		}

		return $response;
	}

	/**
	 * Check if the received heartbeat action was triggered by our heartbeat.js layer
	 *
	 * @param string $uid The task unique id
	 * @return bool
	 */
	private function is_aios_heartbeat($uid) {
		return (bool) preg_match('/^aios-heartbeat-/i', $uid);
	}
}
