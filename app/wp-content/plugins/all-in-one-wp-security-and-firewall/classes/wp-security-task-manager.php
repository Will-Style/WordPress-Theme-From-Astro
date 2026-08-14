<?php
/**
 *  Extends the generic task manager to manage AIOS related queues
 */

if (!defined('ABSPATH')) die('Access denied.');

if (!class_exists('Updraft_Task_Manager_1_4')) require_once(AIO_WP_SECURITY_PATH . '/vendor/team-updraft/common-libs/src/updraft-tasks/class-updraft-task-manager.php');
if (!trait_exists('AIOWPSecurity_Singleton_Trait')) require_once(AIO_WP_SECURITY_PATH . '/classes/traits/wp-security-singleton-trait.php');

if (class_exists('AIOWPSecurity_Task_Manager')) return;

class AIOWPSecurity_Task_Manager extends Updraft_Task_Manager_1_4 {

	use AIOWPSecurity_Singleton_Trait;

	/**
	 * The registered task types.
	 *
	 * @var array
	 */
	private $task_types = array('file_scan');

	/**
	 * The Task Manager constructor
	 */
	public function __construct() {
		parent::__construct();

		add_action('aiowps_process_file_scan_tasks', array($this, 'process_file_scan_tasks'));
		add_action('aiowps_clean_up_old_tasks', array($this, 'clean_up_tasks'));
	}

	/**
	 * Cleans up old tasks of each task type returned by `get_task_types`.
	 *
	 * Iterates through each task type and calls `clean_up_old_tasks` to perform cleanup.
	 *
	 * @return void
	 */
	public function clean_up_tasks() {
		foreach ($this->get_task_types() as $type) {
			$this->clean_up_old_tasks($type);
		}
	}

	/**
	 * Retrieves an array of task types that need periodic cleanup.
	 *
	 * By default, it includes 'file_scan' as a task type. Allows for customization
	 * via the 'aiowps_task_types' filter.
	 *
	 * @return array An array of task type strings.
	 */
	public function get_task_types() {
		return apply_filters('aiowps_task_types', $this->task_types);
	}

	/**
	 * Processes the file scan tasks in the queue, then cleans up the completed tasks.
	 *
	 * Before processing the queue, it first schedules a cron job to re-initiate the process after a certain
	 * interval, ensuring that the process will be completed later in case the current processing fails
	 * or is interrupted. This method can be invoked directly or scheduled as a cron job.
	 */
	public function process_file_scan_tasks() {
		// If there are no pending tasks, nothing to process.
		if (!$this->fetch_active_task('file_scan')) return;

		if (!wp_next_scheduled('aiowps_process_file_scan_tasks')) {
			wp_schedule_single_event(time() + 10, 'aiowps_process_file_scan_tasks');
		}

		$this->process_queue('file_scan');
	}

	/**
	 * Generates a unique task ID
	 *
	 * @return string - Returns a unique task ID
	 */
	public static function generate_unique_task_name() {
		return wp_generate_uuid4();
	}

	/**
	 * Retrieves the first active task of the specified type.
	 *
	 * @param string $task_type The type of task to retrieve, e.g., 'file_scan'.
	 *
	 * @return bool|mixed The first active task object of the specified type, or `false` if no tasks
	 *                    are active or if the task type is invalid.
	 */
	public function fetch_active_task($task_type) {
		if (!in_array($task_type, $this->get_task_types())) return false;
		$tasks = $this->get_active_tasks($task_type);

		return empty($tasks) ? false : $tasks[0];
	}
}
