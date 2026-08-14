<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPSecurity_List_404 extends AIOWPSecurity_Ajax_Data_Table {

	public function __construct($data = array()) {

		//Set parent defaults
		parent::__construct(array(
			'singular' => 'item', //singular name of the listed records
			'plural' => 'items',  //plural name of the listed records
			'ajax' => true,       //does this table support ajax?
			'data' => $data       // Request data
		));
	}

	/**
	 * Renders created column in datetime format as per user setting time zone.
	 *
	 * @param array $item - data for the columns on the current row
	 *
	 * @return void
	 */
	public function column_created($item) {
		echo esc_html(AIOWPSecurity_Utility::convert_timestamp($item['created']));
	}
	
	/**
	 * Renders id column html.
	 *
	 * @param array $item Data for the columns on the current row.
	 *
	 * @return void
	 */
	public function column_id($item) {
		// Build row actions.
		$actions = array(
			'delete' => array(
				'text' => __('Delete', 'all-in-one-wp-security-and-firewall'),
				'attributes' => array(
					'class' => 'aios-delete-404-log',
					'data-id' => $item['id'],
					'data-message' => __('Are you sure you want to delete this item?', 'all-in-one-wp-security-and-firewall'),
				)
			)
		);

		echo esc_html($item['id']);
		$this->row_actions($actions);
	}

	/**
	 * Renders ip_or_host column html.
	 *
	 * @param array $item Data for the columns on the current row.
	 *
	 * @return void
	 */
	public function column_ip_or_host($item) {
		$ip = $item['ip_or_host'];
		$actions = array();
		
		if (AIOWPSecurity_Utility::check_locked_ip($ip, '404')) {
			$actions['unlock_ip'] = array(
				'text' => __('Unlock', 'all-in-one-wp-security-and-firewall'),
				'attributes' => array(
					'class' => 'aios-unlock-ip-button',
					'data-ip' => $ip,
					'data-message' => __('Are you sure you want to unlock this IP address?', 'all-in-one-wp-security-and-firewall'),
				),
			);
		} else {
			$actions['lock_ip'] = array(
				'text' => __('Lock IP', 'all-in-one-wp-security-and-firewall'),
				'attributes' => array(
					'class' => 'aios-lock-ip-button',
					'data-ip' => $ip,
					'data-message' => __('Are you sure you want to temporarily lock this IP address?', 'all-in-one-wp-security-and-firewall'),
					'data-username' => $item['username'],
				),
			);
		}

		if (AIOWPSecurity_Utility_Permissions::is_main_site_and_super_admin()) {
			if (AIOWPSecurity_Utility::check_blacklist_ip($ip)) {
				$actions['unblacklist_ip'] = array(
					'text' => __('Unblacklist', 'all-in-one-wp-security-and-firewall'),
					'attributes' => array(
						'class' => 'aios-unblacklist-ip-button',
						'data-ip' => $ip,
						'data-message' => __('Are you sure you want to unblacklist this IP address?', 'all-in-one-wp-security-and-firewall'),
					),
				);
			} else {
				$actions['blacklist_ip'] = array(
					'text' => __('Blacklist IP', 'all-in-one-wp-security-and-firewall'),
					'attributes' => array(
						'class' => 'aios-blacklist-ip-button',
						'data-ip' => $ip,
						'data-message' => __('Are you sure you want to blacklist this IP address?', 'all-in-one-wp-security-and-firewall'),
					),
				);
			}
		}

		echo esc_html($ip);
		$this->row_actions($actions);
	}

	/**
	 * Renders status column html.
	 *
	 * @param array $item - data for the columns on the current row
	 *
	 * @return void
	 */
	public function column_status($item) {
		$ip = $item['ip_or_host'];
		// Check if this IP address is locked or blacklisted
		$is_locked = AIOWPSecurity_Utility::check_locked_ip($ip, '404');
		$blacklisted = AIOWPSecurity_Utility::check_blacklist_ip($ip);

		if ($blacklisted && $is_locked) {
			esc_html_e('temporarily locked and blacklisted', 'all-in-one-wp-security-and-firewall');
		} elseif ($blacklisted) {
			esc_html_e('blacklisted', 'all-in-one-wp-security-and-firewall');
		} elseif ($is_locked) {
			esc_html_e('temporarily locked', 'all-in-one-wp-security-and-firewall');
		}
	}

	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox
			'id' => 'ID',
			'event_type' => __('Event type', 'all-in-one-wp-security-and-firewall'),
			'ip_or_host' => __('IP address', 'all-in-one-wp-security-and-firewall'),
			'url' => __('Attempted URL', 'all-in-one-wp-security-and-firewall'),
			'referer_info' => __('Referer', 'all-in-one-wp-security-and-firewall'),
			'created' => __('Date and time', 'all-in-one-wp-security-and-firewall'),
			'status' => __('Lock status', 'all-in-one-wp-security-and-firewall'),
		);
		$columns = apply_filters('list_404_get_columns', $columns);
		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array('id', false),
			'event_type' => array('event_type', false),
			'ip_or_host' => array('ip_or_host', false),
			'url' => array('url', false),
			'referer_info' => array('referer_info', false),
			'created' => array('created', false),
		);
		$sortable_columns = apply_filters('list_404_get_sortable_columns', $sortable_columns);
		return $sortable_columns;
	}

	/**
	 * Get bulk actions for the current WordPress screen.
	 *
	 * @return array An associative array of bulk actions where the keys are action names
	 *               and the values are the corresponding action labels.
	 */
	public function get_bulk_actions() {
		$bulk_actions = array(
			'bulk_lock_ip' => __('Lock IP', 'all-in-one-wp-security-and-firewall'),
			'bulk_delete' => __('Delete', 'all-in-one-wp-security-and-firewall'),
		);

		if (AIOWPSecurity_Utility_Permissions::is_main_site_and_super_admin()) {
			$bulk_actions['bulk_blacklist_ip'] = __('Blacklist IP', 'all-in-one-wp-security-and-firewall');
		}

		return $bulk_actions;
	}

	/**
	 * This function will process the bulk action request.
	 *
	 * @param string $action - The bulk action to be performed.
	 * @param array  $items  - An array of record IDs on which the action will be performed. Default is an empty array.
	 *
	 * @return void
	 */
	private function process_bulk_action($action, $items = array()) {
		if (empty($items) || !is_array($items)) {
			AIOS_Helper::set_message('aios_list_message', __('Please select some records using the checkboxes.', 'all-in-one-wp-security-and-firewall'), 'error');
			return;
		}

		switch ($action) {
			case 'bulk_lock_ip':
				$this->lock_ips($items);
				break;
			case 'bulk_blacklist_ip':
				$this->blacklist_ips($items);
				break;
			case 'bulk_delete':
				$this->delete_404_event_records($items);
				break;
			default:
				/* translators: %s: Invalid requested action */
				AIOS_Helper::set_message('aios_list_message', sprintf(__('The requested bulk action is invalid: %s', 'all-in-one-wp-security-and-firewall'), $action), 'error');
				return;
		}
	}

	/**
	 * Temporarily locks multiple IP addresses by adding them to the AIOWPSEC_TBL_LOGIN_LOCKOUT table.
	 *
	 * @param array $entries IDs that correspond to IP addresses in the AIOWPSEC_TBL_EVENTS table.
	 *
	 * @return void
	 */
	private function lock_ips($entries) {
		global $wpdb;

		$entries = array_filter($entries, 'is_numeric'); //discard non-numeric ID values

		$id_list = "(" .implode(",", $entries) .")"; //Create comma separate list for DB operation
		$events_table = AIOWPSEC_TBL_EVENTS;
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery -- PCP error. Ignore.
		$results = $wpdb->get_col("SELECT ip_or_host FROM $events_table WHERE ID IN " . $id_list);

		if (empty($results)) {
			AIOS_Helper::set_message('aios_list_message', __('Could not process the request because the IP addresses for the selected entries could not be found.', 'all-in-one-wp-security-and-firewall'), 'error');
			return;
		} else {
			foreach ($results as $entry) {
				if (filter_var($entry, FILTER_VALIDATE_IP)) {
					AIOWPSecurity_Utility::lock_ip($entry, '404');
				}
			}
		}
		AIOS_Helper::set_message('aios_list_message', __('The selected IP addresses are now temporarily locked.', 'all-in-one-wp-security-and-firewall'));
	}

	/**
	 * Blacklists multiple IP addresses by adding them to the blacklist.
	 *
	 * @param array $entries IDs that correspond to IP addresses in the AIOWPSEC_TBL_EVENTS table.
	 *
	 * @return void
	 */
	private function blacklist_ips($entries) {
		global $wpdb, $aio_wp_security;
		$aiowps_firewall_config = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::CONFIG);

		$bl_ip_addresses = $aio_wp_security->configs->get_value('aiowps_banned_ip_addresses'); //get the currently saved blacklisted IPs
		$ip_list_array = AIOWPSecurity_Utility_IP::create_ip_list_array_from_string_with_newline($bl_ip_addresses);

		//Get the selected IP addresses
		$entries = array_filter($entries, 'is_numeric'); //discard non-numeric ID values
		$id_list = "(" .implode(",", $entries) .")"; //Create comma separate list for DB operation
		$events_table = AIOWPSEC_TBL_EVENTS;
		// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery -- PCP error. Ignore.
		$results = $wpdb->get_col("SELECT ip_or_host FROM $events_table WHERE ID IN " . $id_list);

		if (empty($results)) {
			AIOS_Helper::set_message('aios_list_message', __('Could not process the request because the IP addresses for the selected entries could not be found.', 'all-in-one-wp-security-and-firewall'), 'error');
			return;
		} else {
			foreach ($results as $entry) {
				$ip_list_array[] = $entry;
			}
		}

		$validated_ip_list_array = AIOWPSecurity_Utility_IP::validate_ip_list($ip_list_array, 'blacklist');
		if (is_wp_error($validated_ip_list_array)) {
			AIOS_Helper::set_message('aios_list_message', nl2br($validated_ip_list_array->get_error_message()), 'error');
		} else {
			$banned_ip_data = implode("\n", $validated_ip_list_array);
			$aio_wp_security->configs->set_value('aiowps_enable_blacklisting', '1'); // Force blacklist feature to be enabled.
			$aio_wp_security->configs->set_value('aiowps_banned_ip_addresses', $banned_ip_data);
			$aio_wp_security->configs->save_config();

			$aiowps_firewall_config->set_value('aiowps_blacklist_ips', $validated_ip_list_array);
			$aiowps_firewall_config->set_value('aiowps_enable_blacklisting', '1');

			AIOS_Helper::set_message('aios_list_message', __('The selected IP addresses have been added to the blacklist.', 'all-in-one-wp-security-and-firewall'));
		}
	}

	/**
	 * Deletes one or more records from the AIOWPSEC_TBL_EVENTS table.
	 *
	 * @param array|string|integer $entries - ids or a single id
	 *
	 * @return void
	 */
	public function delete_404_event_records($entries) {
		global $wpdb, $aio_wp_security;
		$events_table = AIOWPSEC_TBL_EVENTS;
		$result = false;

		if (is_array($entries)) {
			//Delete multiple records
			$entries = array_map('esc_sql', $entries); //escape every array element
			$entries = array_filter($entries, 'is_numeric'); //discard non-numeric ID values

			$id_list = "(" . implode(",", $entries) . ")"; //Create comma separate list for DB operation
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- PCP error. Ignore.
			$result = $wpdb->query("DELETE FROM " . $events_table . " WHERE id IN " . $id_list);
		} elseif (null != $entries) {
			//Delete single record
			$result = $wpdb->query($wpdb->prepare("DELETE FROM $events_table WHERE id = %d", absint($entries))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- We can't use %i because our plugin supports wordpress < 6.2.
		}

		if ($result) {
			AIOS_Helper::set_message('aios_list_message', AIOWPSecurity_Admin_Menu::show_msg_record_deleted_st(true, true));
		} else {
			$aio_wp_security->debug_logger->log_debug('Database error occurred when deleting rows from the Events table. Database error: ' . $wpdb->last_error, 4);
			AIOS_Helper::set_message('aios_list_message', AIOWPSecurity_Admin_Menu::show_msg_record_not_deleted_st(true, true), 'error');
		}
	}

	/**
	 * Retrieves all items from AIOWPSEC_TBL_EVENTS according to a search term inside $_REQUEST['s'] and only '404' events if there is no search term. It then assigns to $this->items.
	 *
	 * @param Boolean $ignore_pagination - whether to not paginate
	 *
	 * @return Void
	 */
	public function prepare_items($ignore_pagination = false) {
		/**
		 * First, lets decide how many records per page to show
		 */
		$no_action = '-1';
		$per_page = 100;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$search_term = isset($this->_args['data']['s']) ? sanitize_text_field($this->_args['data']['s']) : '';

		$this->_column_headers = array($columns, $hidden, $sortable);

		$items = array();

		if (isset($this->_args['data']['items'])) {
			if (is_array($this->_args['data']['items'])) {
				foreach ($this->_args['data']['items'] as $item) {
					$sanitized_item = sanitize_text_field($item);
					$items[] = $sanitized_item;
				}
			} else {
				$sanitized_item = sanitize_text_field($this->_args['data']['items']);
				$items[] = $sanitized_item;
			}
		} else {
			$items = null;
		}

		if (isset($this->_args['data']['bulk_apply']) && 'true' === $this->_args['data']['bulk_apply']) {
			$action = isset($this->_args['data']['action']) ? sanitize_text_field($this->_args['data']['action']) : $no_action;

			if ($no_action !== $action) {
				$this->process_bulk_action($action, $items);
			} else {
				AIOS_Helper::set_message('aios_list_message', __('Please select a bulk action from the dropdown.', 'all-in-one-wp-security-and-firewall'), 'error');
			}
		}

		global $wpdb;
		$events_table_name = AIOWPSEC_TBL_EVENTS;

		// Ordering parameters
		// Parameters that are going to be used to order the result
		$orderby = isset($this->_args['data']['orderby']) ? sanitize_text_field($this->_args['data']['orderby']) : '';
		$order = isset($this->_args['data']['order']) ? sanitize_text_field($this->_args['data']['order']) : '';

		$orderby = empty($orderby) ? 'id' : $orderby;

		$orderby = AIOWPSecurity_Utility::sanitize_value_by_array($orderby, $sortable);
		$order = AIOWPSecurity_Utility::sanitize_value_by_array($order, array('DESC' => '1', 'ASC' => '1'));

		if (empty($search_term)) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- PCP warning. Ignore.
			$data = $wpdb->get_results("SELECT * FROM $events_table_name WHERE `event_type` = '404' ORDER BY $orderby $order", ARRAY_A);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQueryWithPlaceholder, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- PCP error. Ignore.
			$data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $events_table_name WHERE `ip_or_host` LIKE '%%%s%%' OR `url` LIKE '%%%s%%' OR `referer_info` LIKE '%%%s%%' ORDER BY $orderby $order", $wpdb->esc_like($search_term), $wpdb->esc_like($search_term), $wpdb->esc_like($search_term)), ARRAY_A);
		}

		if (!$ignore_pagination) {
			$current_page = $this->get_pagenum();
			$total_items = count($data);
			$data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
			$this->set_pagination_args(array(
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page' => $per_page, //WE have to determine how many items to show on a page
				'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
			));
		}

		foreach ($data as $index => $row) {
			// Insert an empty status column - we will use later
			$data[$index]['status'] = '';
		}

		$this->items = $data;
	}

}
