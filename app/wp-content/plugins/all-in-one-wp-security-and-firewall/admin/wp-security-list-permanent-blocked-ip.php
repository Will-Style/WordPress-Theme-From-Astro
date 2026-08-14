<?php

if (!defined('ABSPATH')) die('No direct access.');

/**
 * Permanently blocked IP list table class.
 *
 * Handles rendering, searching, sorting, and bulk actions for blocked IP records.
 */
class AIOWPSecurity_List_Blocked_IP extends AIOWPSecurity_Ajax_Data_Table {

	/**
	 * Constructs the table object and sets up its attributes.
	 *
	 * @param array $data - An array containing additional data for the table. Default is an empty array.
	 * @return void
	 */
	public function __construct($data = array()) {

		// Set parent defaults
		parent::__construct(array(
			'singular' => 'item', // singular name of the listed records
			'plural' => 'items',  // plural name of the listed records
			'ajax' => true,       // does this table support ajax?
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
	 * Renders the permanent blocked ip actions column in the table
	 *
	 * @param array $item - Contains the current item data
	 *
	 * @return void
	 */
	public function column_id($item) {
		$actions = array(
			'unblock' => array(
				'text' => __('Unblock', 'all-in-one-wp-security-and-firewall'),
				'attributes' => array(
					'class' => 'aios-unblock-permanent-ip',
					'data-id' => $item['id'],
					'data-message' => __('Are you sure you want to unblock this IP address?', 'all-in-one-wp-security-and-firewall')
				),
			)
		);

		echo esc_html($item['id']);
		$this->row_actions($actions);
	}

	public function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />', //Render a checkbox
			'id' => 'ID',
			'blocked_ip' => __('Blocked IP', 'all-in-one-wp-security-and-firewall'),
			'block_reason' => __('Reason', 'all-in-one-wp-security-and-firewall'),
			'created' => __('Date and Time', 'all-in-one-wp-security-and-firewall')
		);
	}

	public function get_sortable_columns() {
		return array(
			'id' => array('id', false),
			'blocked_ip' => array('blocked_ip', false),
			'block_reason' => array('block_reason', false),
			'created' => array('created', false)
		);
	}

	public function get_bulk_actions() {
		return array(
			'unblock' => __('Unblock', 'all-in-one-wp-security-and-firewall')
		);
	}

	/**
	 * Process bulk actions from the menu.
	 *
	 * This method processes bulk actions, such as unblocking IP addresses.
	 * Depending on the action passed, it will perform the corresponding task
	 * on the selected items.
	 *
	 * @param string $action The bulk action to be performed.
	 *                       Currently supported value is 'unblock'.
	 * @param array  $items  Optional. An array of records (e.g., IP addresses) to be unblocked.
	 *                       Defaults to an empty array.
	 *
	 * @global string $aios_list_message Global variable used to store a message to be displayed to the user.
	 *
	 * @return void
	 */
	private function process_bulk_action($action, $items = array()) {

		if ('unblock' === $action) { // Process unlock bulk actions
			if (empty($items)) {
				AIOS_Helper::set_message('aios_list_message', __('Please select some records using the checkboxes', 'all-in-one-wp-security-and-firewall'), 'error');
			} else {
				$this->unblock_ip_address($items);
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended -- PCP warning. It IS the nonce. Ignore.
	}

	/**
	 * Deletes one or more records from the AIOWPSEC_TBL_PERM_BLOCK table.
	 *
	 * @param array|string|integer $entries - ids or a single id
	 *
	 * @return void|string
	 */
	public function unblock_ip_address($entries) {
		global $wpdb, $aio_wp_security;

		if (is_array($entries)) {
			$entries = array_map('esc_sql', $entries); // Escape every array element
			$entries = array_filter($entries, 'is_numeric'); // Discard non-numeric ID values
			$chunks = array_chunk($entries, 1000);
			$result = false;

			foreach ($chunks as $chunk) {
				$id_list = "(" . implode(",", $chunk) . ")"; // Create comma separate list for DB operation
				$delete_command = "DELETE FROM " . AIOWPSEC_TBL_PERM_BLOCK . " WHERE id IN " . $id_list;
				// phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.DirectDatabaseQuery -- PCP error. Ignore.
				$result = $wpdb->query($delete_command);

				if (!$result) {
					$aio_wp_security->debug_logger->log_debug('Database error occurred when deleting rows from Perm Block table. Database error: '.$wpdb->last_error, 4);
					AIOS_Helper::set_message('aios_list_message', __('Failed to unblock and delete the selected record(s).', 'all-in-one-wp-security-and-firewall'), 'error');
					return;
				}
			}

			AIOS_Helper::set_message('aios_list_message', __('Successfully unblocked and deleted the selected record(s).', 'all-in-one-wp-security-and-firewall'));
		} elseif (!empty($entries)) {
			//Delete single record
			$delete_command = "DELETE FROM " . AIOWPSEC_TBL_PERM_BLOCK . " WHERE id = %d";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- PCP error. Ignore.
			$result = $wpdb->query($wpdb->prepare($delete_command, absint($entries)));
			if (false === $result) {
				// Error on single delete
				$aio_wp_security->debug_logger->log_debug('Database error occurred when deleting rows from Perm Block table. Database error: '.$wpdb->last_error, 4);
			}

			return $result;
		}
	}

	/**
	 * This function will build and return the SQL WHERE statement
	 *
	 * @param string $search_term - the search term applied
	 *
	 * @return string - the SQL WHERE statement
	 */
	private function get_permanent_blocked_ip_list_where_sql($search_term) {
		$where = '';
		if (!empty($search_term)) {
			$where = " WHERE";

			// We don't use FILTER_VALIDATE_IP here as we want to be able to search for partial IP's
			if (preg_match('/^[0-9a-f:\.]+$/i', $search_term)) {
				$where .= " `blocked_ip` LIKE '%".esc_sql($search_term)."%' OR";
			}

			$where .= " `block_reason` LIKE '%".esc_sql($search_term)."%'";
			$where .= " OR `country_origin` LIKE '%".esc_sql($search_term)."%'";
		}

		return $where;
	}

	/**
	 * Grabs the data from database and handles the pagination
	 *
	 * @param boolean $ignore_pagination - whether to not paginate
	 *
	 * @return void
	 */
	public function prepare_items($ignore_pagination = false) {
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 100;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$search = isset($this->_args['data']['s']) ? sanitize_text_field($this->_args['data']['s']) : '';
		$no_action = '-1';

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
		$block_table_name = AIOWPSEC_TBL_PERM_BLOCK;

		// Ordering parameters
		// Parameters that are going to be used to order the result
		$orderby = isset($this->_args['data']["orderby"]) ? sanitize_text_field($this->_args['data']["orderby"]) : 'id';
		$order = isset($this->_args['data']["order"]) ? sanitize_text_field($this->_args['data']["order"]) : 'DESC';

		$orderby = AIOWPSecurity_Utility::sanitize_value_by_array($orderby, $sortable);
		$order = AIOWPSecurity_Utility::sanitize_value_by_array($order, array('DESC' => '1', 'ASC' => '1'));

		$current_page = $this->get_pagenum();
		$offset = ($current_page - 1) * $per_page;


		$search_query = $this->get_permanent_blocked_ip_list_where_sql($search);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- PCP warning. Ignore.
		$total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$block_table_name}{$search_query}");

		if ($ignore_pagination) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- PCP warning. Ignore.
			$data = $wpdb->get_results("SELECT * FROM {$block_table_name} {$search_query} ORDER BY $orderby $order", 'ARRAY_A');
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- PCP warning. Ignore.
			$data = $wpdb->get_results("SELECT * FROM {$block_table_name}{$search_query} ORDER BY $orderby $order LIMIT $per_page OFFSET $offset", 'ARRAY_A');
		}

		$this->items = $data;

		if ($ignore_pagination) return;

		$this->set_pagination_args(array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page' => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
		));
	}
}
