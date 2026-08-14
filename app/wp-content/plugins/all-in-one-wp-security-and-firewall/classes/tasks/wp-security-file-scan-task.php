<?php

if (!defined('ABSPATH')) die('Access denied.');

if (!class_exists('Updraft_Task_1_2')) require_once(AIO_WP_SECURITY_PATH . '/vendor/team-updraft/common-libs/src/updraft-tasks/class-updraft-task.php');
if (!class_exists('AIOWPSecurity_File_Scanner')) require_once(AIO_WP_SECURITY_PATH . '/classes/wp-security-file-scanner.php');

if (class_exists('AIOWPSecurity_File_Scan_Task')) return;

class AIOWPSecurity_File_Scan_Task extends Updraft_Task_1_2 {

	/**
	 * Initialise the task.
	 *
	 * @param array $options
	 */
	public function initialise($options = array()) {
		parent::initialise($options);
		$this->set_current_stage('initialised');
	}

	/**
	 * Run the file scan. Delegates all scan logic to AIOWPSecurity_File_Scanner,
	 * passing $this so it can report progress back into the task record.
	 */
	public function run() {
		$this->set_current_stage('starting');
		$scanner = new AIOWPSecurity_File_Scanner($this);
		$scanner->execute_file_change_detection_scan();
	}

	/**
	 * Returns the default task options.
	 *
	 * @return array
	 */
	public function get_default_options() {
		return array(
			'current_stage'          => '',
			'number_of_scanned_files' => 0,
			'total_files'            => 0,
		);
	}

	/**
	 * Returns all task options including the task ID.
	 *
	 * @return array
	 */
	public function get_all_options() {
		$options            = parent::get_all_options();
		$options['task_id'] = $this->get_id();
		return $options;
	}

	/**
	 * Sets the current stage if it is an allowed stage.
	 *
	 * @param string $stage
	 * @return bool
	 */
	public function set_current_stage($stage) {
		if (array_key_exists($stage, $this->get_allowed_stages())) {
			return $this->update_option('current_stage', $stage);
		}
		return false;
	}

	/**
	 * Returns the allowed stages for the file scan task.
	 *
	 * @return array
	 */
	public function get_allowed_stages() {
		$stages = array(
			'initialised' => esc_html__('Initiating file scan', 'all-in-one-wp-security-and-firewall'),
			'starting'    => esc_html__('Starting file scan', 'all-in-one-wp-security-and-firewall'),
			'fetching'    => esc_html__('Fetching previous scan data', 'all-in-one-wp-security-and-firewall'),
			'scanning'    => esc_html__('Scanning files', 'all-in-one-wp-security-and-firewall'),
			'comparing'   => esc_html__('Comparing scan data', 'all-in-one-wp-security-and-firewall'),
			'saving'      => esc_html__('Saving file change data', 'all-in-one-wp-security-and-firewall'),
			'failed'      => esc_html__('File scan failed', 'all-in-one-wp-security-and-firewall'),
			'completed'   => esc_html__('Completed file scan', 'all-in-one-wp-security-and-firewall'),
		);
		return apply_filters('aios_allowed_files_scanning_stages', $stages);
	}

	/**
	 * Returns the human-readable label for a given stage key.
	 *
	 * @param string $stage
	 * @return string
	 */
	public function get_stage_status_message($stage) {
		$stages = $this->get_allowed_stages();
		if (empty($stage) || !array_key_exists($stage, $stages)) return $stages['initialised'];
		return $stages[$stage];
	}

	/**
	 * Marks the task as completed and records the scan time.
	 *
	 * @return bool
	 */
	public function complete() {
		global $aio_wp_security;

		if ('failed' === $this->get_option('current_stage')) {
			return false;
		}

		$this->set_current_stage('completed');
		$aio_wp_security->configs->set_value('aiowps_last_scan_time', time(), true);

		return parent::complete();
	}

	/**
	 * Marks the task as failed and sets the failed stage.
	 *
	 * @param string $error_code
	 * @param string $error_message
	 * @return bool
	 */
	public function fail($error_code = 'Unknown', $error_message = 'Unknown') {
		$this->set_current_stage('failed');
		return parent::fail($error_code, $error_message);
	}

	/**
	 * Cancel the task.
	 */
	public function cancel_task() {
		$this->fail('cancelled', esc_html__('The file scan task was cancelled.', 'all-in-one-wp-security-and-firewall'));
	}
}
