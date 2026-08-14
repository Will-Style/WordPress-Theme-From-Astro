<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (trait_exists('AIOWPSecurity_File_Scan_Commands_Trait')) return;

trait AIOWPSecurity_File_Scan_Commands_Trait {

	/**
	 * Perform the operation to save file change detection settings.
	 *
	 * @param array $data The data containing the file change detection settings.
	 *
	 * @return array An array containing the status of the operation, any relevant messages,
	 *               and updated content.
	 */
	public function perform_save_file_detection_change_settings($data) {
		global $aio_wp_security;

		$info = array();
		$content = array();
		$options = array();
		$errors = array();
		$reset_scan_data = false;
		$file_types = '';
		$files = '';

		$fcd_scan_frequency = sanitize_text_field($data['aiowps_fcd_scan_frequency']);

		if (!is_numeric($fcd_scan_frequency)) {
			$errors[] = esc_html__('You entered a non numeric value for the "backup time interval" field, it has been set to the default value.', 'all-in-one-wp-security-and-firewall');
			$fcd_scan_frequency = '4'; // Set it to the default value for this field
		}

		if (!empty($data['aiowps_fcd_exclude_filetypes'])) {
			$file_types = sanitize_textarea_field(trim($data['aiowps_fcd_exclude_filetypes']));

			// Get the currently saved config value and check if this has changed. If so do another scan to reset the scan data so it omits these filetypes
			if ($file_types != $aio_wp_security->configs->get_value('aiowps_fcd_exclude_filetypes')) {
				$reset_scan_data = true;
			}
		}

		if (!empty($data['aiowps_fcd_exclude_files'])) {
			$files = sanitize_textarea_field(trim($data['aiowps_fcd_exclude_files']));
			// Get the currently saved config value and check if this has changed. If so do another scan to reset the scan data so it omits these files/dirs
			if ($files != $aio_wp_security->configs->get_value('aiowps_fcd_exclude_files')) {
				$reset_scan_data = true;
			}
		}

		// Explode by end-of-line character, then trim and filter empty lines
		$email_list_array = array_filter(array_map('trim', explode("\n", $data['aiowps_fcd_scan_email_address'])), 'strlen');
		foreach ($email_list_array as $key => $value) {
			$email_sane = sanitize_email($value);
			if (!is_email($email_sane)) {
				$errors[] = esc_html__('The following address was removed because it is not a valid email address:', 'all-in-one-wp-security-and-firewall') . ' ' . esc_html($value);
				unset($email_list_array[$key]);
			}
		}
		$email_address = implode("\n", $email_list_array);
		if (!empty($errors)) {
			$info[] = implode('<br>', $errors);
		}

		// Save all the form values to the options
		$options['aiowps_enable_automated_fcd_scan'] = isset($data["aiowps_enable_automated_fcd_scan"]) ? '1' : '';
		$options['aiowps_fcd_scan_frequency'] = absint($fcd_scan_frequency);
		$options['aiowps_fcd_scan_interval'] = sanitize_text_field($data["aiowps_fcd_scan_interval"]);
		$options['aiowps_fcd_exclude_filetypes'] = $file_types;
		$options['aiowps_fcd_exclude_files'] = $files;
		$options['aiowps_send_fcd_scan_email'] = isset($data["aiowps_send_fcd_scan_email"]) ? '1' : '';
		$options['aiowps_fcd_scan_email_address'] = $email_address;
		$this->save_settings($options);

		$content['aios-file-change-info-box'] = '';
		// Let's check if backup interval was set to less than 24 hours
		if (isset($data["aiowps_enable_automated_fcd_scan"]) && ($fcd_scan_frequency < 24) && 0 == $data["aiowps_fcd_scan_interval"]) {
			$content['aios-file-change-info-box'] = '<div class="aio_yellow_box">';
			$content['aios-file-change-info-box'] .= '<p>' . esc_html__('You have configured your file change detection scan to occur at least once daily.', 'all-in-one-wp-security-and-firewall') . '</p>';
			$content['aios-file-change-info-box'] .= '<p>' . esc_html__('For most websites we recommended that you choose a less frequent schedule such as once every few days, once a week or once a month.', 'all-in-one-wp-security-and-firewall') . '</p>';
			$content['aios-file-change-info-box'] .= '<p>' . esc_html__('Choosing a less frequent schedule will also help reduce your server load.', 'all-in-one-wp-security-and-firewall') . '</p>';
			$content['aios-file-change-info-box'] .= '</div>';
		}

		if ($reset_scan_data) {
			$this->initiate_file_scan();
			$new_scan_alert = esc_html__('New scan completed: The plugin has detected that you have made changes to the "File Types To Ignore" or "Files To Ignore" fields.', 'all-in-one-wp-security-and-firewall').' '.esc_html__('In order to ensure that future scan results are accurate, the old scan data has been refreshed.', 'all-in-one-wp-security-and-firewall');
			$info[] = $new_scan_alert;
		}

		$next_fcd_scan_time = AIOWPSecurity_File_Scanner::get_next_scheduled_scan();

		if (false == $next_fcd_scan_time) {
			$next_scheduled_scan = '<span>' . esc_html__('Nothing is currently scheduled', 'all-in-one-wp-security-and-firewall') . '</span>';
		} else {
			$scan_time = AIOWPSecurity_Utility::convert_timestamp($next_fcd_scan_time, 'D, F j, Y H:i');
			$next_scheduled_scan = '<span class="aiowps_next_scheduled_date_time">' . esc_html($scan_time) . '</span>';
		}

		$content['aiowps-next-files-scan-inner'] = $next_scheduled_scan;
		$values = array('aiowps_fcd_scan_frequency' => absint($fcd_scan_frequency));
		$badges = array('scan-file-change-detection');

		$args = array(
			'content' => $content,
			'values' => $values,
			'badges' => $badges,
			'info' => $info
		);

		return $this->handle_response(true, '', $args);
	}

	/**
	 * Initializes or resumes the active file scan task and returns its current status.
	 *
	 * Checks for an existing active `file_scan` task. If none exists, creates a new
	 * scan task, assigns it a unique task name, and schedules the background cron
	 * event (`aiowps_process_file_scan_tasks`) if it is not already scheduled.
	 *
	 * Once a task exists, retrieves its current stage data and status message,
	 * then formats the response payload for the caller.
	 *
	 * @return array Response data from `handle_response()` containing:
	 *               - success status (bool)
	 *               - current stage/status message (string|false)
	 *               - task arguments including `extra_args` (array|false)
	 *
	 *               Returns a failure response if task creation fails.
	 */
	public function initiate_file_scan() {
		$task = $this->task_manager->fetch_active_task('file_scan');

		if (!$task) {
			$task_name = "aiowps_file_scan_" . $this->task_manager->generate_unique_task_name();
			$task = AIOWPSecurity_File_Scan_Task::create_task('file_scan', $task_name);

			if (!$task) return $this->handle_response(false, false);

			if (!wp_next_scheduled('aiowps_process_file_scan_tasks')) {
				wp_schedule_single_event(time() + 3, 'aiowps_process_file_scan_tasks');
			}
		}

		$extra_args = $task->get_all_options();
		$message = $task->get_stage_status_message($extra_args['current_stage']);

		$args = array('extra_args' => $extra_args);

		return $this->handle_response(true, $message, $args);
	}
	/**
	 * Retrieves the last file scan data and returns the data to UDC.
	 *
	 * @param array $data The request data.
	 *
	 * @return array|string[]|WP_Error
	 */
	public function get_last_scan_data($data) {
		global $aio_wp_security;

		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		if ($data['reset_change_detected']) {
			$aio_wp_security->configs->set_value('aiowps_fcds_change_detected', false, true);
		}

		$fcd_data = AIOWPSecurity_File_Scanner::get_fcd_data();

		$data = $fcd_data['last_scan_result'];

		foreach (array('files_added', 'files_removed', 'files_changed') as $key) {
			/* Normalize missing or non-array buckets to an empty array and skip processing */
			if (!isset($data[$key]) || !is_array($data[$key])) {
				$data[$key] = array();
				continue;
			}

			/* Convert last_modified for each entry */
			foreach ($data[$key] as &$info) {
				if (is_array($info) && array_key_exists('last_modified', $info) && is_numeric($info['last_modified'])) {
					$info['last_modified'] = AIOWPSecurity_Utility::convert_timestamp($info['last_modified']);
				}
			}

			unset($info);
		}

		$fcd_data['last_scan_result'] = $data;

		return $this->handle_response(true, false, array('extra_args' => $fcd_data));
	}

	/**
	 * Gets the last file scan result and returns the scan result HTML template
	 *
	 * @param array $data - the request data
	 *
	 * @return array
	 */
	public function get_last_scan_results($data) {
		global $aio_wp_security;

		if ($data['reset_change_detected']) $aio_wp_security->configs->set_value('aiowps_fcds_change_detected', false, true);

		$fcd_data = AIOWPSecurity_File_Scanner::get_fcd_data();

		if (!$fcd_data || !isset($fcd_data['last_scan_result'])) {
			// no fcd data found
			$message = esc_html__('No previous scan data was found; either run a manual scan or schedule regular file scans', 'all-in-one-wp-security-and-firewall');
			return $this->handle_response(false, $message);
		}

		$content = array('aiowps_previous_scan_wrapper' => $aio_wp_security->include_template('wp-admin/scanner/scan-result.php', true, array('fcd_data' => $fcd_data)));

		return $this->handle_response(true, false, array('content' => $content));
	}

	/**
	 * Retrieves the current update status for a file scan task.
	 *
	 * @param array $data An associative array containing the task data, including:
	 *                    - 'task_id' (int) The ID of the file scan task.
	 *
	 * @return mixed The response from `handle_response`, containing the task status,
	 *               current stage message, and any relevant scan results or content updates.
	 */
	public function get_file_scan_update($data) {
		global $aio_wp_security;

		$task_id = isset($data['task_id']) ? absint($data['task_id']) : 0;
		$task = $this->task_manager->get_task_instance($task_id);

		if (!$task) return $this->handle_response(false, esc_html__('No file scan task found', 'all-in-one-wp-security-and-firewall'));

		$content = array();

		$options = $task->get_all_options();
		$message = $task->get_stage_status_message($options['current_stage']);
		$success = true;

		if ('completed' === $options['current_stage']) {
			if (!empty($options['initial_scan'])) {
				$options['scan_status'] = 'initial_scan';
				$content['aiowps-previous-files-scan-inner'] = '<a href="#" class="aiowps_view_last_fcd_results">' . esc_html__('View last file scan results', 'all-in-one-wp-security-and-firewall') . '</a>';
			} elseif (!$aio_wp_security->configs->get_value('aiowps_fcds_change_detected')) {
				$options['scan_status'] = 'no_changes';
			} else {
				$options['scan_status'] = 'changes_detected';
			}
			$last_fcd_scan_time = $aio_wp_security->configs->get_value('aiowps_last_scan_time');
			$last_scan_time = AIOWPSecurity_Utility::convert_timestamp($last_fcd_scan_time, 'D, F j, Y H:i');
			$last_scan = '<span class="aiowps_last_date_time">' . esc_html($last_scan_time) . '</span>';
			$content['aiowps-last-scan-time-inner'] = $last_scan;
		} elseif ('failed' == $options['current_stage']) {
			$options['scan_status'] = 'failed';
			$success = false;
		}

		$args = array('extra_args' => $options, 'content' => $content);

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * Cancels a file scan task.
	 *
	 * @param array $data - the request data
	 *
	 * @return array
	 */
	public function cancel_file_scan($data) {
		$task_id = isset($data['task_id']) ? absint($data['task_id']) : 0;
		$task = $this->task_manager->get_task_instance($task_id);

		if (!$task) return $this->handle_response(false, esc_html__('No file scan task found', 'all-in-one-wp-security-and-firewall'));
		if ('completed' === $task->get_all_options()['current_stage']) return $this->handle_response(false, esc_html__('The file scan task has already been completed and cannot be cancelled.', 'all-in-one-wp-security-and-firewall'));

		$task->cancel_task();

		$options = $task->get_all_options();
		$message = esc_html__('The file scan has been cancelled.', 'all-in-one-wp-security-and-firewall');

		$args = array('extra_args' => $options);

		return $this->handle_response(true, $message, $args);
	}

	/**
	 * Render the legacy UDC Scanner.
	 *
	 * @return array
	 */
	public function get_scanner_contents() {
		global $aio_wp_security;

		$GLOBALS['aiowps_feature_mgr'] = $this->get_feature_mgr_object();

		$scanner_data = AIOWPSecurity_Utility_File::get_scanner_data();

		$content = $aio_wp_security->include_template('wp-admin/scanner/file-change-detect.php', true, $scanner_data);

		return array(
			'status' => 'success',
			'content' => $content,
		);
	}

	/**
	 * Return file scanner data.
	 *
	 * @return array Array of option values,
	 */
	public function get_scanner_data() {
		global $aio_wp_security;

		$fcd_data = AIOWPSecurity_File_Scanner::get_fcd_data();
		$previous_scan = isset($fcd_data['last_scan_result']);

		$next_fcd_scan_time = AIOWPSecurity_File_Scanner::get_next_scheduled_scan();

		$aiowps_fcds_change_detected = $aio_wp_security->configs->get_value('aiowps_fcds_change_detected');
		$aiowps_enable_automated_fcd_scan = $aio_wp_security->configs->get_value('aiowps_enable_automated_fcd_scan');
		$aiowps_fcd_scan_frequency = $aio_wp_security->configs->get_value('aiowps_fcd_scan_frequency');
		$aiowps_fcd_scan_interval = $aio_wp_security->configs->get_value('aiowps_fcd_scan_interval');
		$aiowps_fcd_exclude_filetypes = $aio_wp_security->configs->get_value('aiowps_fcd_exclude_filetypes');
		$aiowps_fcd_exclude_files = $aio_wp_security->configs->get_value('aiowps_fcd_exclude_files');
		$aiowps_send_fcd_scan_email = $aio_wp_security->configs->get_value('aiowps_send_fcd_scan_email');
		$aiowps_fcd_scan_email_address = $aio_wp_security->configs->get_value('aiowps_fcd_scan_email_address');
		$aiowps_last_scan_time = $aio_wp_security->configs->get_value('aiowps_last_scan_time');

		return array(
			'previous_scan' => $previous_scan,
			'next_fcd_scan_time' => false === $next_fcd_scan_time ? '' : AIOWPSecurity_Utility::convert_timestamp($next_fcd_scan_time, 'D, F j, Y H:i'),
			'aiowps_fcds_change_detected' => $aiowps_fcds_change_detected,
			'aiowps_enable_automated_fcd_scan' => $aiowps_enable_automated_fcd_scan,
			'aiowps_fcd_scan_frequency' => $aiowps_fcd_scan_frequency,
			'aiowps_fcd_scan_interval' => $aiowps_fcd_scan_interval,
			'aiowps_fcd_exclude_filetypes' => $aiowps_fcd_exclude_filetypes,
			'aiowps_fcd_exclude_files' => $aiowps_fcd_exclude_files,
			'aiowps_send_fcd_scan_email' => $aiowps_send_fcd_scan_email,
			'aiowps_fcd_scan_email_address' => $aiowps_fcd_scan_email_address,
			'aiowps_last_scan_time' => AIOWPSecurity_Utility::convert_timestamp($aiowps_last_scan_time, 'D, F j, Y H:i'),
		);
	}
}
