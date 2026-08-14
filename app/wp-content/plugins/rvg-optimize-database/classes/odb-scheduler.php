<?php
/************************************************************************************************
 *
 *	SCHEDULER CLASS
 *
 ************************************************************************************************/
?>
<?php
class ODB_Scheduler {

	/********************************************************************************************
	 *	CONSTRUCTOR
	 ********************************************************************************************/
    function __construct() {
		global $odb_class;

		// ADD EXTRA CRON SCHEDULES
		add_filter('cron_schedules', array(&$this, 'odb_extra_cron_schedules'));

		// ADD SCHEDULER
		add_action('odb_scheduler', array(&$odb_class, 'odb_start_scheduler'));
	} // __construct()


	/*******************************************************************************
	 * 	ADD EXTRA SCHEDULE FOR THE CRONTAB
	 *	http://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
	 *
	 *	v4.0.1	Localization fixed
	 *	v4.0.3	($schedules) added as a parameter
	 *	v4.2.0	Added monthly
	 *******************************************************************************/
	function odb_extra_cron_schedules($schedules) {
		global $odb_class;

		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => __('Once Weekly', 'rvg-optimize-database')
		);
		$schedules['monthly'] = array(
			'interval' => 2628000, // average amount of seconds in a month
			'display'  => __('Once Monthly', 'rvg-optimize-database')
		);
		// FOR DEBUGGING
		$schedules['fiveminutes'] = array(
			'interval' => 300,
			'display'  => __('Every Five Minutes', 'rvg-optimize-database')
		);
		return $schedules;
	} // odb_extra_cron_schedules()


	/*******************************************************************************
	 * 	UPDATE SCHEDULER (IF NEEDED)
	 *******************************************************************************/
	function odb_update_scheduler() {
		global $odb_class;

		if($odb_class->odb_rvg_options['schedule_type'] == '') {
			// SHOULDN'T BE SCHEDULED
			wp_clear_scheduled_hook('odb_scheduler');
			$odb_class->odb_rvg_options['schedule_hour'] = '';
			$odb_class->odb_multisite_obj->odb_ms_update_option('odb_rvg_options', $odb_class->odb_rvg_options);
		} else {
			// JOB SHOULD BE SCHEDULED: SCHEDULE IT
			if($odb_class->odb_rvg_options['schedule_type'] != 'daily' &&
				$odb_class->odb_rvg_options['schedule_type'] != 'weekly' &&
				$odb_class->odb_rvg_options['schedule_type'] != 'monthly'
				) {
				$odb_class->odb_rvg_options['schedule_hour'] = '';
				$odb_class->odb_multisite_obj->odb_ms_update_option('odb_rvg_options', $odb_class->odb_rvg_options);
			}

			if (!wp_next_scheduled('odb_scheduler'))
				wp_schedule_event($this->odb_calculate_time(), $odb_class->odb_rvg_options['schedule_type'], 'odb_scheduler');
		} // if($odb_class->odb_rvg_options['schedule_type'] == '')
	} // odb_update_scheduler()


	/*******************************************************************************
	 * 	SCHEDULE CHANGED ON SETTINGS PAGE: RESCHEDULE
	 *******************************************************************************/
	function odb_reschedule() {
		global $odb_class;

		wp_clear_scheduled_hook('odb_scheduler');
		wp_schedule_event($this->odb_calculate_time(), $odb_class->odb_rvg_options['schedule_type'], 'odb_scheduler');
	} // odb_reschedule()


	/*******************************************************************************
	 * 	CALCULATE SCHEDULE TIME, BASED ON THE SCHEDULE TYPE
	 *
	 *	v4.5	Fixed time issues
	 *******************************************************************************/
	function odb_calculate_time() {
		global $odb_class;

		// CURRENT TIME (WITH TIMEZONE)
		$timestamp = current_time('timestamp', 1);
		// YYYYMMDDHHMMSS
		$ymdhis    = date('YmdHis', $timestamp);

		// CHOP TIME, YYYYMMDD
		$current_date = substr($ymdhis, 0, 8);
		$current_hour = substr($ymdhis, 8, 2);

		if ($odb_class->odb_rvg_options['schedule_type'] == 'daily' ||
				$odb_class->odb_rvg_options['schedule_type'] == 'weekly' ||
				$odb_class->odb_rvg_options['schedule_type'] == 'monthly'
				) {
			// 'daily', 'weekly' OR 'monthly'
			if($odb_class->odb_rvg_options['schedule_hour'] <= $current_hour) {
				// NEXT RUN WILL BE TOMORROW
				$date = date('YmdHis', strtotime($current_date.$odb_class->odb_rvg_options['schedule_hour'].'0000'.' + 1 day'));
			} else {
				// NEXT RUN WILL BE TODAY
				$date = $current_date.$odb_class->odb_rvg_options['schedule_hour'].'0000';
			} // if($odb_class->odb_rvg_options['schedule_hour'] <= $current_hour)
		} else {
			// 'hourly' OR 'twicedaily'

			// ADD ONE HOUR TO THE CURRENT TIME: IT WILL RUN THE NEXT FULL HOUR (16:00 FOR INSTANCE)
			$ts   = $timestamp + 3600;
			$date = date('YmdH0000', $ts);
		} // if ($odb_class->odb_rvg_options['schedule_type'] == 'daily' ...

		// CONVERT TO TIMESTAMP
		return strtotime($date);
	} // odb_calculate_time()
} // ODB_Scheduler
?>