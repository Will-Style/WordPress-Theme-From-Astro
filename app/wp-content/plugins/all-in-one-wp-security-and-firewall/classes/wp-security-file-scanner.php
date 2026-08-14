<?php

if (!defined('ABSPATH')) die('Access denied.');

if (class_exists('AIOWPSecurity_File_Scanner')) return;

/**
 * Performs the actual file change detection scan work.
 *
 * Performs the actual file change detection scan work, delegated to by AIOWPSecurity_File_Scan_Task.
 * When a task is provided, scan progress and stage changes are written to the task record.
 */
class AIOWPSecurity_File_Scanner {

	/**
	 * The task providing progress tracking, or null when running outside a task.
	 *
	 * @var AIOWPSecurity_File_Scan_Task|null
	 */
	private $task;

	/**
	 * Filenames to skip during a file change detection scan.
	 *
	 * @var string[]
	 */
	private static $ignored_files = array(
		'wp-security-log-cron-job.txt',
		'wp-security-log.txt',
	);

	/**
	 * Constructor.
	 *
	 * @param AIOWPSecurity_File_Scan_Task|null $task Optional task for progress/stage reporting.
	 */
	public function __construct($task = null) {
		$this->task = $task;
	}

	// -------------------------------------------------------------------------
	// Progress helpers — all calls are no-ops when no task is attached.
	// -------------------------------------------------------------------------

	/**
	 * Set the current task stage.
	 *
	 * @param string $stage
	 */
	private function set_stage($stage) {
		if ($this->task) {
			$this->task->set_current_stage($stage);
		}
	}

	/**
	 * Update a task option.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	private function update_option($key, $value) {
		if ($this->task) {
			$this->task->update_option($key, $value);
		}
	}

	/**
	 * Fail the task with an error code and message.
	 *
	 * @param string $code
	 * @param string $message
	 */
	private function fail($code, $message) {
		if ($this->task) {
			$this->task->fail($code, $message);
		}
	}

	private function complete() {
		if ($this->task) {
			$this->task->complete();
		}
	}

	// -------------------------------------------------------------------------
	// Public scan API
	// -------------------------------------------------------------------------

	/**
	 * Entry point: run the full file-change detection scan.
	 *
	 * @global AIO_WP_Security $aio_wp_security
	 * @return bool|array False on failure, scan result array on success.
	 */
	public function execute_file_change_detection_scan() {
		global $aio_wp_security;

		$scan_result = array();

		$this->set_stage('fetching');
		$fcd_data = self::get_fcd_data();

		if (false === $fcd_data) {
			$this->fail('file_error', esc_html__('Error fetching file change detection data.', 'all-in-one-wp-security-and-firewall'));
			return false;
		}

		AIOWPSecurity_Checksums::update_checksums();

		$scanned_data = $this->do_file_change_scan();
		$initial_scan = false;

		if (empty($fcd_data)) {
			$this->save_fcd_data($scanned_data);
			$scan_result['initial_scan'] = '1';
			$initial_scan = true;
		} else {
			$scan_result = $this->compare_scan_data($fcd_data['file_scan_data'], $scanned_data);
			$scan_result['initial_scan'] = '';
			$this->save_fcd_data($scanned_data, $scan_result);

			if (!empty($scan_result['files_added']) || !empty($scan_result['files_removed']) || !empty($scan_result['files_changed'])) {
				$aio_wp_security->configs->set_value('aiowps_fcds_change_detected', true, true);
				$aio_wp_security->debug_logger->log_debug(__METHOD__ . ' - change to filesystem detected!');
				$this->aiowps_send_file_change_alert_email($scan_result);
			} else {
				$aio_wp_security->configs->set_value('aiowps_fcds_change_detected', false, true);
			}
		}

		$this->update_option('initial_scan', $initial_scan ? '1' : '');

		$this->complete();

		return $scan_result;
	}

	/**
	 * Get or generate the filename used to store file change detection data.
	 *
	 * @global AIO_WP_Security $aio_wp_security
	 * @return string
	 */
	private static function get_fcd_filename() {
		global $aio_wp_security;
		$fcd_filename = $aio_wp_security->configs->get_value('aiowps_fcd_filename');
		if (empty($fcd_filename)) {
			$random_suffix = AIOWPSecurity_Utility::generate_alpha_numeric_random_string(10);
			$fcd_filename = 'aiowps_fcd_data_' . $random_suffix;
			$aio_wp_security->configs->set_value('aiowps_fcd_filename', $fcd_filename, true);
		}
		return $fcd_filename;
	}

	/**
	 * Get the last filechange detection data stored in the special file.
	 *
	 * @global AIO_WP_Security $aio_wp_security
	 * @return bool|array False on failure, array on success (empty array = no prior scan).
	 */
	public static function get_fcd_data() {
		// phpcs:disable WordPress.WP.AlternativeFunctions
		global $aio_wp_security;
		$aiowps_backup_dir = WP_CONTENT_DIR . '/' . AIO_WP_SECURITY_BACKUPS_DIR_NAME;

		$fcd_filename = self::get_fcd_filename();

		$results_file = $aiowps_backup_dir . '/' . $fcd_filename;

		if (!is_file($results_file)) {
			if (is_dir($results_file)) {
				rename($results_file, $results_file . '_backup');
			}
			return array();
		}

		if (empty(filesize($results_file))) {
			return array();
		}

		$fp = @fopen($results_file, 'r');
		if (false === $fp) {
			$aio_wp_security->debug_logger->log_debug(__METHOD__ . ' - fopen returned false when opening fcd data file');
			return false;
		}

		$contents = fread($fp, filesize($results_file));
		fclose($fp);

		if (false === $contents) {
			$aio_wp_security->debug_logger->log_debug(__METHOD__ . ' - fread returned false when reading fcd data file');
			return false;
		}

		$fcd_file_contents = json_decode($contents, true);
		if (isset($fcd_file_contents['file_scan_data'])) {
			return $fcd_file_contents;
		}

		return array();
		// phpcs:enable WordPress.WP.AlternativeFunctions
	}

	/**
	 * Recursively scan $start_dir and return file size and last-modified for every regular file,
	 * respecting the exclusion settings.
	 *
	 * @global AIO_WP_Security $aio_wp_security
	 * @param string $start_dir
	 * @return array
	 */
	public function do_file_change_scan($start_dir = ABSPATH) {
		global $aio_wp_security;

		$filescan_data = array();
		$dit = new RecursiveDirectoryIterator($start_dir, FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS);
		$rit = new RecursiveIteratorIterator($dit, RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);

		$files_to_skip = AIOWPSecurity_Utility::explode_trim_filter_empty($aio_wp_security->configs->get_value('aiowps_fcd_exclude_files'));
		$file_types_to_skip = AIOWPSecurity_Utility::explode_trim_filter_empty(strtolower($aio_wp_security->configs->get_value('aiowps_fcd_exclude_filetypes')));

		$start_dir_length = strlen($start_dir);
		$number_of_scanned_files = 0;
		$total_files = iterator_count($rit);

		$this->update_option('total_files', $total_files);
		$this->update_option('number_of_scanned_files', $number_of_scanned_files);
		$this->set_stage('scanning');

		foreach ($rit as $filename => $fileinfo) {
			$number_of_scanned_files++;

			if (0 === $number_of_scanned_files % 100 || $number_of_scanned_files === $total_files) {
				$this->update_option('number_of_scanned_files', $number_of_scanned_files);
			}

			if (!file_exists($filename) || is_dir($filename)) {
				continue;
			}

			if (in_array($fileinfo->getFilename(), self::$ignored_files, true)) {
				continue;
			}

			if (!empty($file_types_to_skip)) {
				$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
				if (in_array($ext, $file_types_to_skip)) continue;
			}

			if (!empty($files_to_skip)) {
				$skip_this = false;
				foreach ($files_to_skip as $f_or_dir) {
					if (false !== strpos($filename, $f_or_dir, $start_dir_length)) {
						$skip_this = true;
						break;
					}
				}
				if ($skip_this) continue;
			}

			try {
				$filescan_data[$filename] = array(
					'last_modified' => $fileinfo->getMTime(),
					'filesize'      => $fileinfo->getSize(),
				);
			} catch (Exception $e) {
				$aio_wp_security->debug_logger->log_debug(__METHOD__ . " - Exception when getting file info for $filename: " . $e->getMessage());
			}
		}

		return $filescan_data;
	}

	/**
	 * Compare previous and current scan data and return a diff.
	 *
	 * @param array $last_scan_data
	 * @param array $new_scanned_data
	 * @return array
	 */
	public function compare_scan_data($last_scan_data, $new_scanned_data) {
		$this->set_stage('comparing');

		// ensure they are arrays or just cast
		$new_scanned_data = is_array($new_scanned_data) ? $new_scanned_data : (array) $new_scanned_data;
		$last_scan_data = is_array($last_scan_data) ? $last_scan_data : (array) $last_scan_data;

		$files_added   = array_diff_key($new_scanned_data, $last_scan_data);
		$files_removed = array_diff_key($last_scan_data, $new_scanned_data);
		$files_kept    = array_diff_key($new_scanned_data, $files_added);
		$files_changed = array();

		foreach ($files_kept as $filename => $new_scan_meta) {
			$last_scan_meta = $last_scan_data[$filename];
			$checksum_result = AIOWPSecurity_Checksums::verify_file_checksums($filename);
			
			if (!$checksum_result['success']) {
				$new_scan_meta['reason']    = 'checksum_mismatch';
				$new_scan_meta['checksums'] = $checksum_result['checksums'];
				$files_changed[$filename]   = $new_scan_meta;
			} elseif ($new_scan_meta['last_modified'] !== $last_scan_meta['last_modified'] || $new_scan_meta['filesize'] !== $last_scan_meta['filesize']) {
				$new_scan_meta['reason']  = 'metadata_mismatch';
				$files_changed[$filename] = $new_scan_meta;
			}
		}

		return array(
			'files_added'   => $files_added,
			'files_removed' => $files_removed,
			'files_changed' => $files_changed,
		);
	}

	/**
	 * Saves file change detection data into the special file.
	 *
	 * @global AIO_WP_Security $aio_wp_security
	 * @param array $scanned_data
	 * @param array $scan_result
	 * @return bool
	 */
	public function save_fcd_data($scanned_data, $scan_result = array()) {
		// phpcs:disable WordPress.WP.AlternativeFunctions
		global $aio_wp_security;

		$this->set_stage('saving');

		$fcd_filename      = $aio_wp_security->configs->get_value('aiowps_fcd_filename');
		$aiowps_backup_dir = WP_CONTENT_DIR . '/' . AIO_WP_SECURITY_BACKUPS_DIR_NAME;

		if (!AIOWPSecurity_Utility_File::create_dir($aiowps_backup_dir)) {
			$aio_wp_security->debug_logger->log_debug(__METHOD__ . ' - Creation of DB backup directory failed!', 4);
			$this->fail('db_error', esc_html__('Creation of DB backup directory failed!', 'all-in-one-wp-security-and-firewall'));
			return false;
		}

		$data         = array('date_time' => current_time('mysql'), 'file_scan_data' => $scanned_data, 'last_scan_result' => $scan_result);
		$results_file = $aiowps_backup_dir . '/' . $fcd_filename;
		$fp           = fopen($results_file, 'w');

		if (false === $fp) {
			$aio_wp_security->debug_logger->log_debug(__METHOD__ . ' - fopen failed for results file.', 4);
			$this->fail('file_error', esc_html__('Could not open scan results file for writing.', 'all-in-one-wp-security-and-firewall'));
			return false;
		}

		fwrite($fp, json_encode($data));
		fclose($fp);

		return true;
		// phpcs:enable WordPress.WP.AlternativeFunctions
	}

	/**
	 * Send an email alert when file changes are detected.
	 *
	 * @global AIO_WP_Security $aio_wp_security
	 * @param array $scan_result
	 */
	public function aiowps_send_file_change_alert_email($scan_result) {
		global $aio_wp_security;

		if ('1' != $aio_wp_security->configs->get_value('aiowps_send_fcd_scan_email')) return;

		$subject = esc_html__('All In One Security - File change detected', 'all-in-one-wp-security-and-firewall') . ' ' . AIOWPSecurity_Utility::convert_timestamp(null, 'l, F jS, Y \a\\t g:i a');
		/* translators: %s: Site URL */
		$message = sprintf(esc_html__('A file change was detected on your system for site URL %s.', 'all-in-one-wp-security-and-firewall'), network_site_url());
		/* translators: %s: Date and time of the scan */
		$message .= ' ' . sprintf(esc_html__('Scan was generated on %s.', 'all-in-one-wp-security-and-firewall'), AIOWPSecurity_Utility::convert_timestamp(null, 'l, F jS, Y \a\\t g:i a'));
		$message .= "\r\n\r\n" . esc_html__('A summary of the scan results is shown below:', 'all-in-one-wp-security-and-firewall');
		$message .= "\r\n\r\n";
		$message .= self::get_file_change_summary($scan_result);
		$message .= "\r\n" . esc_html__('Login to your site to view the scan details.', 'all-in-one-wp-security-and-firewall');

		$addresses = AIOWPSecurity_Utility::get_array_from_textarea_val($aio_wp_security->configs->get_value('aiowps_fcd_scan_email_address'));
		$to        = empty($addresses) ? array(get_site_option('admin_email')) : $addresses;

		$mail_sent = AIOWPSecurity_Reporting::notification(array(
			'to'      => $to,
			'subject' => $subject,
			'message' => $message,
		));

		if (false === $mail_sent) {
			$aio_wp_security->debug_logger->log_debug(__METHOD__ . ' - File change notification email failed to send.', 4);
		}
	}

	/**
	 * Build a plain-text summary of a scan result suitable for emails or display.
	 *
	 * @param array $scan_result
	 * @return string
	 */
	public static function get_file_change_summary($scan_result) {
		$scan_summary = '';

		if (!empty($scan_result['files_added'])) {
			$scan_summary .= "\r\n" . esc_html__('The following files were added to your host', 'all-in-one-wp-security-and-firewall') . ":\r\n";
			foreach ($scan_result['files_added'] as $key => $value) {
				$scan_summary .= "\r\n" . $key . ' (' . esc_html__('modified on:', 'all-in-one-wp-security-and-firewall') . ' ' . AIOWPSecurity_Utility::convert_timestamp($value['last_modified']) . ')';
			}
			$scan_summary .= "\r\n======================================\r\n";
		}

		if (!empty($scan_result['files_removed'])) {
			$scan_summary .= "\r\n" . esc_html__('The following files were removed from your host', 'all-in-one-wp-security-and-firewall') . ":\r\n";
			foreach ($scan_result['files_removed'] as $key => $value) {
				$scan_summary .= "\r\n" . $key . ' (' . esc_html__('modified on:', 'all-in-one-wp-security-and-firewall') . ' ' . AIOWPSecurity_Utility::convert_timestamp($value['last_modified']) . ')';
			}
			$scan_summary .= "\r\n======================================\r\n";
		}

		if (!empty($scan_result['files_changed'])) {
			$scan_summary .= "\r\n" . esc_html__('The following files were changed on your host', 'all-in-one-wp-security-and-firewall') . ":\r\n";
			foreach ($scan_result['files_changed'] as $key => $value) {
				$scan_summary .= "\r\n" . $key . ' (' . esc_html__('modified on:', 'all-in-one-wp-security-and-firewall') . ' ' . AIOWPSecurity_Utility::convert_timestamp($value['last_modified']) . ')';
			}
			$scan_summary .= "\r\n======================================\r\n";
		}

		return $scan_summary;
	}

	/**
	 * Returns the next scheduled scan timestamp, or false if automated scans are disabled.
	 *
	 * @global AIO_WP_Security $aio_wp_security
	 * @return int|bool
	 */
	public static function get_next_scheduled_scan() {
		global $aio_wp_security;

		if ('1' != $aio_wp_security->configs->get_value('aiowps_enable_automated_fcd_scan')) return false;

		$fcd_scan_frequency = $aio_wp_security->configs->get_value('aiowps_fcd_scan_frequency');
		$interval_setting   = $aio_wp_security->configs->get_value('aiowps_fcd_scan_interval');

		switch ($interval_setting) {
			case '0':
				$interval = 'hours';
				break;
			case '1':
				$interval = 'days';
				break;
			case '2':
			default:
				$interval = 'weeks';
				break;
		}

		$last_fcd_scan_time = $aio_wp_security->configs->get_value('aiowps_last_fcd_scan_time');
		if (null == $last_fcd_scan_time) {
			$last_fcd_scan_time = time();
			$aio_wp_security->configs->set_value('aiowps_last_fcd_scan_time', $last_fcd_scan_time, true);
		} elseif (is_string($last_fcd_scan_time)) {
			$last_fcd_scan_time = strtotime($last_fcd_scan_time);
			$aio_wp_security->configs->set_value('aiowps_last_fcd_scan_time', $last_fcd_scan_time, true);
		}

		return strtotime('+' . abs($fcd_scan_frequency) . ' ' . $interval, $last_fcd_scan_time);
	}
}
