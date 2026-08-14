<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (class_exists('AIOWPSecurity_Commands')) return;

if (!trait_exists('AIOWPSecurity_Log_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-log-commands.php');
if (!trait_exists('AIOWPSecurity_Ip_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-ip-commands.php');
if (!trait_exists('AIOWPSecurity_Brute_Force_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-brute-force-commands.php');
if (!trait_exists('AIOWPSecurity_Comment_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-comment-commands.php');
if (!trait_exists('AIOWPSecurity_User_Security_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-user-security-commands.php');
if (!trait_exists('AIOWPSecurity_Settings_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-settings-commands.php');
if (!trait_exists('AIOWPSecurity_Files_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-files-commands.php');
if (!trait_exists('AIOWPSecurity_Firewall_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-firewall-commands.php');
if (!trait_exists('AIOWPSecurity_Tools_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-tools-commands.php');
if (!trait_exists('AIOWPSecurity_File_Scan_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-file-scan-commands.php');
if (!trait_exists('AIOWPSecurity_Tfa_Commands_Trait')) require_once(AIO_WP_SECURITY_PATH.'/classes/commands/wp-security-tfa-commands.php');

if (!class_exists('Updraft_Task_Manager_Commands_1_0')) require_once(AIO_WP_SECURITY_PATH . '/vendor/team-updraft/common-libs/src/updraft-tasks/class-updraft-task-manager-commands.php');

class AIOWPSecurity_Commands extends Updraft_Task_Manager_Commands_1_0 {

	use AIOWPSecurity_Log_Commands_Trait;
	use AIOWPSecurity_Ip_Commands_Trait;
	use AIOWPSecurity_Brute_Force_Commands_Trait;
	use AIOWPSecurity_Comment_Commands_Trait;
	use AIOWPSecurity_User_Security_Commands_Trait;
	use AIOWPSecurity_Settings_Commands_Trait;
	use AIOWPSecurity_Files_Commands_Trait;
	use AIOWPSecurity_Firewall_Commands_Trait;
	use AIOWPSecurity_Tools_Commands_Trait;
	use AIOWPSecurity_File_Scan_Commands_Trait;
	use AIOWPSecurity_Tfa_Commands_Trait;

	/**
	 * Retrieves a list of allowed heartbeat commands.
	 *
	 * Defines heartbeat commands specifically allowed for scanner-related actions. The list
	 * can be extended using the 'aios_allowed_heartbeat_commands' filter.
	 *
	 * @return array Filtered list of allowed heartbeat commands.
	 */
	public static function get_allowed_heartbeat_commands() {
		$commands = array(
			'get_file_scan_update'
		);

		return apply_filters('aios_allowed_heartbeat_commands', $commands);
	}

	/**
	 * This variable holds an instance of AIOWPSecurity_Feature_Item_Manager.
	 *
	 * @var AIOWPSecurity_Feature_Item_Manager $aiowps_feature_mgr
	 */
	private $aiowps_feature_mgr;


	/**
	 * The commands constructor
	 */
	public function __construct() {
		parent::__construct(AIOWPSecurity_Task_Manager::get_instance());
	}

	/**
	 * Retrieves a list of allowed task types.
	 *
	 * Defines and filters the types of tasks that the scanner can handle, such as file scans.
	 * The list is extendable via the 'aios_allowed_task_types' filter.
	 *
	 * @return array Filtered list of allowed task types.
	 */
	protected static function get_allowed_task_types() {
		$types = array(
			'file_scan'
		);

		return apply_filters('aios_allowed_task_types', $types);
	}

	/**
	 * Checks for a currently running task of a specified type.
	 *
	 * Validates if the provided task type is within the allowed task types and checks if there
	 * are any active tasks of this type. If a running task is found, it returns its data; otherwise,
	 * it returns a failed response.
	 *
	 * @param array $data Contains the 'type' key indicating the task type to check.
	 *
	 * @return array Response object with task details if a task is running; otherwise, an unsuccessful response.
	 */
	public function check_running_task($data) {
		$type = isset($data['type']) ? $data['type'] : '';

		if (!in_array($type, self::get_allowed_task_types())) return $this->handle_response(false);

		$tasks = $this->get_active_tasks($data);

		if (empty($tasks)) return $this->handle_response(false);

		return $this->handle_response(true, false, array('extra_args' => array('task_id' => $tasks[0])));
	}

	/**
	 * The initializes the AIOWPS Feature Manager
	 *
	 * @return bool
	 */
	private function feature_mgr_init() {
		static $initialized = false;
		if ($initialized && !empty($this->aiowps_feature_mgr)) return true;

		$this->aiowps_feature_mgr = new AIOWPSecurity_Feature_Item_Manager();

		$initialized = true;

		return true;
	}

	/**
	 * Retrieves the feature manager object.
	 *
	 * This method initializes the feature manager if necessary and returns the
	 * AIOWPSecurity_Feature_Item_Manager instance. If the initialization fails or
	 * the feature manager object is empty, it returns a WP_Error.
	 *
	 * @return AIOWPSecurity_Feature_Item_Manager|WP_Error
	 */
	private function get_feature_mgr_object() {

		$do_init = $this->feature_mgr_init();

		if (true === $do_init && !empty($this->aiowps_feature_mgr)) return $this->aiowps_feature_mgr;

		return new WP_Error('not_initialized', __('The feature item manager could not be initialized.', 'all-in-one-wp-security-and-firewall'));
	}

	/**
	 * Get IP address of given method.
	 *
	 * @param array $data - the request data
	 *
	 * @return array|WP_Error - an array response or a WP_Error if there was an error
	 */
	public function get_ip_address_of_given_method($data) {
		$ip_method_id = $data['ip_retrieve_method'];
		$ip_retrieve_methods = AIOS_Abstracted_Ids::get_ip_retrieve_methods();
		if (isset($ip_retrieve_methods[$ip_method_id])) {
			return array(
				'ip_address' => isset($_SERVER[$ip_retrieve_methods[$ip_method_id]]) ? sanitize_text_field(wp_unslash($_SERVER[$ip_retrieve_methods[$ip_method_id]])) : '',
			);
		} else {
			return new WP_Error('aios-invalid-ip-retrieve-method', __('Invalid IP retrieve method.', 'all-in-one-wp-security-and-firewall'));
		}
		die;
	}

	/**
	 * Dismiss a notice
	 *
	 * @param array $data - the request data contains the notice to dismiss
	 *
	 * @return array
	 */
	public function dismiss_notice($data) {
		global $aio_wp_security;
		global $aiowps_firewall_config;

		$time_now = $aio_wp_security->notices->get_time_now();
		
		if (in_array($data['notice'], array('dismissdashnotice', 'dismiss_season'))) {
			$aio_wp_security->configs->set_value($data['notice'], $time_now + (366 * 86400));
		} elseif (in_array($data['notice'], array('dismiss_page_notice_until', 'dismiss_notice'))) {
			$aio_wp_security->configs->set_value($data['notice'], $time_now + (84 * 86400));
		} elseif ('dismiss_review_notice' == $data['notice']) {
			if (empty($data['dismiss_forever'])) {
				$aio_wp_security->configs->set_value($data['notice'], $time_now + (84 * 86400));
			} else {
				$aio_wp_security->configs->set_value($data['notice'], $time_now + (100 * 365.25 * 86400));
			}
		} elseif ('dismiss_automated_database_backup_notice' == $data['notice']) {
			$aio_wp_security->delete_automated_backup_configs();
		} elseif ('dismiss_ip_retrieval_settings_notice' == $data['notice']) {
			$aio_wp_security->configs->set_value($data['notice'], 1);
		} elseif ('dismiss_ip_retrieval_settings_notice' == $data['notice']) {
			$aio_wp_security->configs->set_value('aiowps_is_login_whitelist_disabled_on_upgrade', 1);
		} elseif ('dismiss_login_whitelist_disabled_on_upgrade_notice' == $data['notice']) {
			if (isset($data['turn_it_back_on']) && '1' == $data['turn_it_back_on']) {
				$aio_wp_security->configs->set_value('aiowps_enable_whitelisting', '1');
			}
			$aio_wp_security->configs->delete_value('aiowps_is_login_whitelist_disabled_on_upgrade');
		} elseif ('dismiss_ip_blacklist_notice' == $data['notice']) {
			if (isset($data['turn_it_back_on']) && '1' == $data['turn_it_back_on']) {
				$aio_wp_security->configs->set_value('aiowps_enable_blacklisting', '1');
				$aiowps_firewall_config->set_value('aiowps_enable_blacklisting', '1');
				AIOWPSecurity_Configure_Settings::set_blacklist_ip_firewall_configs();
				AIOWPSecurity_Configure_Settings::set_user_agent_firewall_configs();
			}
			$aio_wp_security->configs->delete_value('aiowps_is_ip_blacklist_settings_notice_on_upgrade');
		} elseif ('dismiss_firewall_settings_disabled_on_upgrade_notice' == $data['notice']) {
			$is_reactivated = (isset($data['turn_it_back_on']) && '1' == $data['turn_it_back_on']);
				if ($is_reactivated) {
					$aiowps_firewall_config = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::CONFIG);
					$active_settings = $aio_wp_security->configs->get_value('aiowps_firewall_active_upgrade');
	
					if (!empty($active_settings)) {
						$active_settings = json_decode($active_settings);
						if (!empty($active_settings)) {
							foreach ($active_settings as $setting) {
								$aiowps_firewall_config->set_value($setting, true);
							}
						}
					}
				}

				$aio_wp_security->configs->delete_value('aiowps_firewall_active_upgrade');
		} elseif ('php_73_eol_dismiss_forever' == $data['notice']) {
			$aio_wp_security->configs->set_value('php_73_eol_dismiss_forever', $time_now + (100 * 365.25 * 86400));
		}
		

		$aio_wp_security->configs->save_config();
		
		return array();
	}


	/**
	 * This is a helper function to save settings options using key/value pairs
	 *
	 * @param array $options  - An array of options to save to the config
	 * @param null  $callback - A callback function to call when the options are saved
	 *
	 * @return bool
	 */
	public function save_settings($options, $callback = null) {
		global $aio_wp_security;

		$aiowps_feature_mgr = $this->get_feature_mgr_object();
		if (is_wp_error($aiowps_feature_mgr)) return false;


		foreach ($options as $key => $value) {
			$aio_wp_security->configs->set_value($key, $value);
		}
		//commit the config changes
		$aio_wp_security->configs->save_config();

		if (is_callable($callback)) {
			call_user_func($callback, $options);
		}

		$aiowps_feature_mgr->calculate_total_feature_points();

		return true;
	}

	/**
	 * This is a helper function to get the output feature details badge
	 *
	 * @param string $feature_id - the id of the feature we want to get the badge for
	 *
	 * @return string
	 */
	public function get_feature_details_badge($feature_id) {
		$aiowps_feature_mgr = $this->get_feature_mgr_object();
		if (is_wp_error($aiowps_feature_mgr)) return '';
		return $aiowps_feature_mgr->output_feature_details_badge($feature_id, true);
	}

	/**
	 * Retrieves the IDs and HTML content for features.
	 *
	 * This method processes an array of features and returns an associative array containing
	 * the IDs and corresponding HTML content for each feature badge.
	 *
	 * @param array $features - An array containing the features to retrieve IDs and HTML for.
	 *
	 * @return array An associative array containing the IDs and HTML content for each feature badge.
	 */
	public function get_features_id_and_html($features) {
		$result = array();
		foreach ($features as $feature) {
			$result[] = array(
				'id' => '#' . $feature . '-badge',
				'html' => $this->get_feature_details_badge($feature)
			);
		}

		return $result;
	}

	/**
	 * Prepares and returns a structured response for AJAX commands.
	 *
	 * @param bool        $success Indicates whether the operation was successful (true for success, false for failure).
	 * @param string|bool $message The message to include in the response (optional).
	 *                             If false, no message is passed with the response.
	 *                             If empty string, it defaults to a success or error message based on the $success flag.
	 * @param array       $args    Optional. An associative array of additional response data, such as badges, info, values, or content.
	 *
	 * @return array The constructed response array containing status, message, and any additional data from $args.
	 */
	public function handle_response($success, $message = '', $args = array()) {
		$response = array(
			'status' => $success ? 'success' : 'error',
		);

		if (false !== $message) {
			$response['message'] = $this->get_message($success, $message);
		}

		$allowed_keys = array('badges', 'info', 'values', 'content', 'extra_args');
		foreach ($allowed_keys as $key) {
			if (!empty($args[$key])) {
				$response[$key] = 'badges' === $key ? $this->get_features_id_and_html($args[$key]) : $args[$key];
			}
		}

		return $response;
	}

	/**
	 * Get the appropriate message based on success flag and provided message.
	 *
	 * @param bool   $success Indicates whether the operation was successful.
	 * @param string $message The provided message.
	 *
	 * @return string The final message to be used in the response.
	 */
	private function get_message($success, $message) {
		if ('' === $message) {
			return $success ? __('The settings have been successfully updated.', 'all-in-one-wp-security-and-firewall') : __('The settings update was unsuccessful.', 'all-in-one-wp-security-and-firewall');
		}
		return $message;
	}

	/**
	 * Get antibot keys for the spam detection
	 *
	 * @return array
	 */
	public function get_antibot_keys() {
		global $aio_wp_security;
		
		$response = array(
			'status' => 'success',
			'data' => array(),
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- PCP warning. It is the nonce.
		$nonce = empty($_POST['nonce']) ? '' : sanitize_key(wp_unslash($_POST['nonce']));
		if (!wp_verify_nonce($nonce, 'wp-security-ajax-nonce')) {
			$response['status'] = false;
			$response['error_code'] = 'invalid_nonce';
			$response['error_message'] = 'Invalid nonce (wp-security-ajax-nonce) provided for this action.';
		} else {
			$key_map_arr = AIOWPSecurity_Comment::generate_antibot_keys(true);
			$response['data'] = $key_map_arr[0];
			if ('1' == $aio_wp_security->configs->get_value('aiowps_spambot_detect_usecookies')) {
				AIOWPSecurity_Comment::insert_antibot_keys_in_cookie();
			}
		}
		
		echo wp_json_encode($response);
		exit;
	}

	/**
	 * This function will send the diagnostic report email
	 *
	 * @param array $data - the request data
	 *
	 * @return array
	 */
	public function send_report_email($data) {
		global $aio_wp_security;

		// Sanitize the email address first.
		$sanitized_email = !empty($data['report_email']) ? sanitize_email($data['report_email']) : '';

		if ('' === $sanitized_email || !is_email($sanitized_email)) {
			return array(
				'status' => 'error',
				'message' => __('Invalid email address.', 'all-in-one-wp-security-and-firewall'),
			);
		}

		$result = $aio_wp_security->debug_obj->send_report($sanitized_email, wp_kses_post(html_entity_decode($data['report_sections'])));

		if ($result) {
			return array(
				'status' => 'success',
				'message' => __('The diagnostic report has been sent successfully.', 'all-in-one-wp-security-and-firewall'),
			);
		}

		return array(
			'status' => 'error',
			'message' => __('There was an error sending the diagnostic report.', 'all-in-one-wp-security-and-firewall'),
		);
	}

	/**
	 * Returns an array of translations used by the AIOS plugin. Primarily used for UpdraftCentral consumption.
	 *
	 * @param array $params - The parameters passed to the function.
	 *
	 * @return array - The AIOS translations array
	 */
	public function get_js_translation($params) {
		$translations = array();

		if (isset($params['return_formatted_response'])) {
			return array(
				'error' => false,
				'data' => array(
					'translations' => $translations,
				)
			);
		}

		return $translations;
	}

	/**
	 * Get multiple widgets data. Primarily used for UpdraftCentral consumption.
	 *
	 * @param array $args - The arguments containing the widgets to retrieve data for.
	 *
	 * @return array - An array containing error status and data for each widget.
	 */
	public function get_widgets_data($args) {
		// Widgets.
		$widgets = isset($args['widgets']) ? $args['widgets'] : array();

		// Return early if no widgets supplied.
		if (!is_array($widgets) || empty($widgets)) {
			return array('error' => false, 'data' => array());
		}

		// Get the data for each widget.
		$data = array();

		// Loop through the widgets and get their data.
		foreach ($widgets as $widget) {
			$method = 'get_' . $widget . '_data';

			if (method_exists($this, $method)) {
				$data[$widget] = $this->$method($args);
			}
		}

		return array('error' => false, 'data' => $data);
	}

	/**
	 * Get the AIOS premium upsell data for UDC widget.
	 *
	 * @return array
	 */
	public function get_is_premium_data() {
		$is_premium = AIOWPSecurity_Utility_Permissions::is_premium_installed();
		$upgrade_to_premium_data = array();

		if (false === $is_premium) {
			$upgrade_to_premium_data = array(
				'heading' => __('AIOS premium', 'all-in-one-wp-security-and-firewall'),
				'checklist' => array(
					__('Advanced malware scanning', 'all-in-one-wp-security-and-firewall'),
					__('Real-time response time monitoring', 'all-in-one-wp-security-and-firewall'),
					__('Custom two-factor authentication', 'all-in-one-wp-security-and-firewall'),
					__('404 error protection & bot blocking', 'all-in-one-wp-security-and-firewall'),
					__('Country-based traffic blocking', 'all-in-one-wp-security-and-firewall'),
					__('Country whitelist management', 'all-in-one-wp-security-and-firewall'),
					__('Guaranteed premium support', 'all-in-one-wp-security-and-firewall'),
				),
				'cta' => array(
					'text' => __('Upgrade Now', 'all-in-one-wp-security-and-firewall'),
					'url' => 'https://teamupdraft.com/all-in-one-security/pricing/',
				),
			);
		}

		return array(
			'is_premium' => $is_premium,
			'upgrade_to_premium_data' => $upgrade_to_premium_data,
		);
	}

	/**
	 * Get report data for the current month.
	 *
	 * @return array - An array containing this month's report data.
	 */
	public function get_report_data() {
		$start_time = strtotime('first day of this month 00:00:00');
		$end_time = strtotime('last day of this month 23:59:59');
		global $wpdb;

		$block_table_name = AIOWPSEC_TBL_PERM_BLOCK;
		$blocked_ips_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$block_table_name}
				WHERE created BETWEEN %d AND %d",
				$start_time,
				$end_time
			)
		);

		$audit_log_table_name = AIOWPSEC_TBL_AUDIT_LOG;
		$failed_login_attempts_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$audit_log_table_name}
				WHERE event_type='failed_login' AND created BETWEEN %d AND %d",
				$start_time,
				$end_time
			)
		);

		$stopped_malicious_requests_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) 
				FROM {$audit_log_table_name}
				WHERE event_type='rule_triggered' AND created BETWEEN %d AND %d",
				$start_time,
				$end_time
			)
		);

		$file_change_detection_data = AIOWPSecurity_File_Scanner::get_fcd_data();

		$critical_features = AIOWPSecurity_Feature_Item_Manager::get_critical_features();
		$aiowps_feature_mgr = $this->get_feature_mgr_object();
		$aiowps_feature_mgr->check_feature_status_and_recalculate_points();

		$critical_issues = 0;

		foreach ($critical_features as $key => $feature) {
			$feature_item = $aiowps_feature_mgr->get_feature_item_by_id($key);
			if (!$feature_item) continue;

			if (!$feature_item->is_active()) {
				++$critical_issues;
			}
		}

		return array(
			'report_data' => array(
				'login_attempts_blocked' => empty($failed_login_attempts_count) ? 0 : $failed_login_attempts_count,
				'stopped_malicious_requests' => empty($stopped_malicious_requests_count) ? 0 : $stopped_malicious_requests_count,
				'blocked_ips_count' => empty($blocked_ips_count) ? 0 : $blocked_ips_count,
				'total_files_scanned' => empty($file_change_detection_data['file_scan_data']) ? 0 : count($file_change_detection_data['file_scan_data']),
			),
			'variables' => array(
				/* translators: %d is the number of critical issues found. */
				'security_highlights' => sprintf(__('%d critical issue(s)', 'all-in-one-wp-security-and-firewall'), $critical_issues),
			),
		);
	}
}
