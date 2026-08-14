<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (trait_exists('AIOWPSecurity_Log_Commands_Trait')) return;

trait AIOWPSecurity_Log_Commands_Trait {

	/**
	 * Deletes an audit log.
	 *
	 * @param array $data Contains the ID of the log to be deleted.
	 *
	 * @return array
	 */
	public function delete_audit_log($data) {

		if (!isset($data['id'])) {
			return $this->handle_response(false, __('No audit log ID provided.', 'all-in-one-wp-security-and-firewall'));
		}

		include_once AIO_WP_SECURITY_PATH.'/admin/wp-security-list-audit.php';
		$audit_log_list = new AIOWPSecurity_List_Audit_Log();
		$audit_log_list->delete_audit_event_records($data['id']);

		$list_message = AIOS_Helper::get_message('aios_list_message');

		return $this->handle_response('error' != $list_message['type'], $list_message['message']);
	}

	/**
	 * Deletes a 404 log.
	 *
	 * @param array $data Contains the ID of the log to be deleted.
	 *
	 * @return array
	 */
	public function delete_404_log($data) {

		if (!isset($data['id'])) {
			return $this->handle_response(false, __('No 404 log ID provided.', 'all-in-one-wp-security-and-firewall'));
		}

		include_once AIO_WP_SECURITY_PATH.'/admin/wp-security-list-404.php';
		$event_list_404 = new AIOWPSecurity_List_404();
		$event_list_404->delete_404_event_records($data['id']);

		$list_message = AIOS_Helper::get_message('aios_list_message');

		return $this->handle_response('error' != $list_message['type'], $list_message['message']);
	}

	/**
	 * Deletes an IP lockout record.
	 *
	 * @param array $data Contains the ID of the entry in the AIOWPSEC_TBL_LOGIN_LOCKOUT table.
	 *
	 * @return array
	 */
	public function delete_locked_ip_record($data) {

		if (!isset($data['id'])) {
			return $this->handle_response(false, AIOWPSecurity_Admin_Menu::show_msg_error_st(__('No locked IP record ID provided.', 'all-in-one-wp-security-and-firewall'), true));
		}

		include_once AIO_WP_SECURITY_PATH . '/admin/wp-security-list-locked-ip.php';

		$locked_ip_list = new AIOWPSecurity_List_Locked_IP();
		$result = $locked_ip_list->delete_lockout_records($data['id']);
		return $this->handle_response(true, $result);
	}
		
	/**
	 * Clear debug logs
	 *
	 * @return array
	 */
	public function clear_debug_logs() {
		global $aio_wp_security;

		$ret = $aio_wp_security->debug_logger->clear_logs();
		
		if (is_wp_error($ret)) {
			return $this->handle_response(false, AIOWPSecurity_Admin_Menu::show_msg_error_st(esc_html($ret->get_error_message()).'<p>'.esc_html($ret->get_error_data()).'</p>', true));
		} else {
			return $this->handle_response(true, AIOWPSecurity_Admin_Menu::show_msg_updated_st(__('The debug logs have been cleared.', 'all-in-one-wp-security-and-firewall'), true));
		}
	}

	/**
	 * Renders the audit log tab content.
	 *
	 * This function handles the rendering of the audit log tab content based on the
	 * provided data via AJAX request. The data is used to filter the audit log or perform actions
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function render_audit_log_tab($data) {
		$data = empty($data) ? array() : $data;

		if (isset($data['items'])) $data['items'] = array_map('sanitize_text_field', $data['items']);

		// Needed for rendering the audit log table
		include_once(AIO_WP_SECURITY_PATH.'/admin/wp-security-list-audit.php');
		$audit_log_list = new AIOWPSecurity_List_Audit_Log($data);
		$audit_log_list->ajax_response();
	}

	/**
	 * Renders the permanent block list tab content.
	 *
	 * @access public
	 *
	 * @param array $data Permanent blocked IP data.
	 *
	 * @return void
	 */
	public function render_permanent_block_list_tab($data) {
		$data = empty($data) ? array() : $data;

		// Needed for rendering the block list table
		include_once(AIO_WP_SECURITY_PATH.'/admin/wp-security-list-permanent-blocked-ip.php');
		$permanent_blocked_ip_list = new AIOWPSecurity_List_Blocked_IP($data);
		$permanent_blocked_ip_list->ajax_response();
	}

	/**
	 * Renders the 404 log table content.
	 *
	 * This function handles the rendering of the 404 log table content based on the
	 * provided data via AJAX request. The data is used to perform actions.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function render_404_log_tab($data) {
		$data = empty($data) ? array() : $data;

		// Needed for rendering the 404 log table
		include_once(AIO_WP_SECURITY_PATH.'/admin/wp-security-list-404.php');
		$event_list_404 = new AIOWPSecurity_List_404($data);
		$event_list_404->ajax_response();
	}

	/**
	 * Exports the audit logs as a CSV file and sends the data as an AJAX response.
	 *
	 * This function retrieves audit log data, prepares it for export, and generates a CSV string.
	 * The CSV data is then sent back as part of an AJAX response, along with the filename for the CSV file.
	 *
	 * @return array
	 */
	public function export_audit_logs() {

		// Needed for rendering the audit log table
		include_once(AIO_WP_SECURITY_PATH.'/admin/wp-security-list-audit.php');
		$audit_log_list = new AIOWPSecurity_List_Audit_Log();

		$audit_log_list->prepare_items(true);

		// Prevent empty CSV downloads when no records exist.
		if (empty($audit_log_list->items)) {
			return array(
				'success' => false,
				'status'  => 'error',
				'message' => __('No audit logs available to export.', 'all-in-one-wp-security-and-firewall')
			);
		}

		$export_keys = array(
			'id' => 'ID',
			'created' => __('Date and time', 'all-in-one-wp-security-and-firewall'),
			'level' => __('Level', 'all-in-one-wp-security-and-firewall'),
			'network_id' => __('Network ID', 'all-in-one-wp-security-and-firewall'),
			'site_id' => __('Site ID', 'all-in-one-wp-security-and-firewall'),
			'username' => __('Username', 'all-in-one-wp-security-and-firewall'),
			'ip' => __('IP', 'all-in-one-wp-security-and-firewall'),
			'event_type' => __('Event', 'all-in-one-wp-security-and-firewall'),
			'details' => __('Details', 'all-in-one-wp-security-and-firewall'),
			'stacktrace' => __('Stack trace', 'all-in-one-wp-security-and-firewall')
		);

		// Add country column for Premium.
		if (AIOWPSecurity_Utility_Permissions::is_premium_installed()) {
			$export_keys['country_code'] = __('Country', 'all-in-one-wp-security-and-firewall');
		}

		$title = 'audit_event_logs.csv';
		ob_start();
		AIOWPSecurity_Admin_Init::aiowps_output_csv($audit_log_list->items, $export_keys, $title);

		$data = ob_get_clean();

		return array(
			'title' => $title,
			'data' => $data
		);
	}

	/**
	 * Initializing the WP List API, since UDC commands do not load all parts of WP.
	 *
	 * @return void
	 */
	private function init_wp_list() {
		if (!function_exists('submit_button')) {
			require_once(ABSPATH . 'wp-admin/includes/template.php');
		}

		if (!function_exists('render_screen_reader_content')) {
			require_once(ABSPATH . 'wp-admin/includes/class-wp-screen.php');
		}

		if (!function_exists('get_column_headers')) {
			require_once(ABSPATH . 'wp-admin/includes/screen.php');
		}
	}

	/**
	 * Returns the data for downloading the audit log.
	 *
	 * @return array|WP_Error
	 */
	public function process_audit_log_export() {
		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		$this->init_wp_list();

		return $this->export_audit_logs();
	}

	/**
	 * Returns the HTML for the audit log.
	 *
	 * @return array
	 */
	public function get_audit_log_contents() {
		global $aio_wp_security;

		$this->init_wp_list();

		// Needed for rendering the audit log table
		include_once AIO_WP_SECURITY_PATH . '/admin/wp-security-list-audit.php';
		$data = array();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- PCP warning. Processing form data without nonce verification. No nonce.
		if (isset($_GET['event-filter'])) $data['event-filter'] = sanitize_text_field(wp_unslash($_GET['event-filter'])); // Failed logins and logins only to show as audit log
		$audit_log_list = new AIOWPSecurity_List_Audit_Log($data);

		$tab = isset($_GET["tab"]) ? sanitize_text_field(wp_unslash($_GET["tab"])) : '';
		$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended -- PCP warning. Processing form data without nonce verification. No nonce.

		$content = $aio_wp_security->include_template('wp-admin/dashboard/audit-logs.php', true, array('audit_log_list' => $audit_log_list, 'page' => $page, 'tab' => $tab));

		return array(
			'status' => 'success',
			'content' => $content,
		);
	}

	/**
	 * Deletes entry from audit log.
	 *
	 * @param array $data Table config data.
	 *
	 * @return array|WP_Error
	 */
	public function do_delete_audit_log($data) {
		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		$this->init_wp_list();

		if (!class_exists('AIOWPSecurity_Admin_Menu')) {
			include_once AIO_WP_SECURITY_PATH . '/admin/wp-security-admin-menu.php';
		}

		return $this->delete_audit_log($data);
	}

	/**
	 * Renders audit log after actions (delete/orderby, block/unblock, etc.)
	 *
	 * @param array $data Table config data.
	 *
	 * @return array
	 */
	public function do_render_audit_log_tab($data) {
		$this->init_wp_list();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- PCP warning. Nonce checked in previous function.
		if (empty($data)) return array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- PCP warning. Nonce checked in previous function and sanitization done at later.
		$data = wp_unslash($data);

		if (!class_exists('AIOWPSecurity_Admin_Menu')) {
			include_once AIO_WP_SECURITY_PATH . '/admin/wp-security-admin-menu.php';
		}

		// Needed for rendering the audit log table
		include_once(AIO_WP_SECURITY_PATH.'/admin/wp-security-list-audit.php');
		$audit_log_list = new AIOWPSecurity_List_Audit_Log($data);

		return $audit_log_list->ajax_response(true);
	}

	/**
	 * Parses raw audit log data for human-readable output.
	 *
	 * @param AIOWPSecurity_List_Audit_Log $audit_log_list Audit log object.
	 * @param array                        $data           Raw audit log data.
	 *
	 * @return array
	 */
	private function parse_audit_log_data($audit_log_list, $data) {
		$items = array();

		foreach ($data as $db_item) {
			if (empty($db_item)) {
				continue;
			}

			$item = array();

			foreach ($db_item as $key => $value) {
				switch ($key) {
					case 'created':
						$item[$key] = AIOWPSecurity_Utility::convert_timestamp($value);
						break;
					case 'event_type':
						$item[$key] = $audit_log_list->column_event_type($db_item);
						break;
					case 'details':
						$item[$key] = $audit_log_list->column_details($db_item);
						break;
					case 'stacktrace':
						$item[$key] = $audit_log_list->column_stacktrace($db_item);
						break;
					default:
						$item[$key] = $value;
						break;
				}
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Returns the data for the audit log table.
	 *
	 * @param array $data Configuration data.
	 *
	 * @return array
	 */
	public function get_audit_log_data($data) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- PCP warning. Nonce checked in previous function and sanitization done at later.
		$data = isset($data) ? wp_unslash($data) : array();

		$data = isset($data['data']) ? $data['data'] : $data;

		$this->init_wp_list();
		$final_column = array();

		// Needed for rendering the audit log table
		include_once(AIO_WP_SECURITY_PATH.'/admin/wp-security-list-audit.php');
		$audit_log_list = new AIOWPSecurity_List_Audit_Log($data);

		$audit_log_list->prepare_items();

		list($columns, $hidden) = $audit_log_list->get_column_info();

		foreach ($columns as $column_key => $column_display_name) {
			if ('cb' !== $column_key) {
				if (!in_array($column_key, $hidden, true)) {
					$final_column[$column_key] = array('label' => $column_display_name);
				}
			}
		}

		$audit_log_items = isset($audit_log_list->items) ? $audit_log_list->items : array();

		foreach ($audit_log_items as $key => $item) {
			$ip = isset($item['ip']) ? $item['ip'] : '';

			if ('' !== $ip) {
				$audit_log_items[$key]['is_ip_locked'] = AIOWPSecurity_Utility::check_locked_ip($ip, 'audit-log');
				$audit_log_items[$key]['is_ip_blacklisted'] = AIOWPSecurity_Utility::check_blacklist_ip($ip);
			}
		}

		$items = $this->parse_audit_log_data($audit_log_list, $audit_log_items);

		$bulk_actions = $audit_log_list->get_bulk_actions();

		$paged = !empty($data['paged']) ? (int) $data['paged'] : 1;

		AIOWPSecurity_Audit_Events::setup_event_types();

		return array(
			'audit_log_data' => array(
				'bulk_actions' => $bulk_actions,
				'event_types' => AIOWPSecurity_Audit_Events::$event_types,
				'log_levels' => AIOWPSecurity_Audit_Events::$log_levels,
				'columns' => $final_column,
				'items' => $items,
				'is_multisite' => is_multisite(),
				'pagination' => array('page' => $paged, 'pages' => $audit_log_list->get_pagination_arg('total_pages'), 'results' => $audit_log_list->get_pagination_arg('total_items')),
			),
		);
	}
}
