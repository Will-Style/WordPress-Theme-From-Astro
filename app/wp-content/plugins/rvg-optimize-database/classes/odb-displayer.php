<?php
/************************************************************************************************
 *
 *	DISPLAYER CLASS: DISPLAY HEADERS, CURRENT SETTINGS, BUTTONS
 *
 ************************************************************************************************/
?>
<?php
class ODB_Displayer {
	/********************************************************************************************
	 *	CONSTRUCTOR
	 ********************************************************************************************/
    function __construct() {
	} // __construct()


	/********************************************************************************************
	 *	DISPLAY THE PAGE HEADER
	 ********************************************************************************************/
	function display_header() {
		global $odb_class;

		// V4.1.9: RUNNING INDICATOR ADDED
		echo '
<div id="odb-running" style="display:none"></div>
<div id="odb-header" class="odb-padding-left">
  <div id="odb-options-opening">
    <div class="odb-title-bar">
      <h2>'.__('Optimize Database after Deleting Revisions','rvg-optimize-database').'</h2>
    </div>
    <div class="odb-subheader-container">
      <div class="odb-subheader-left">
        <p class="odb-bold"> <em>'.__('One-click database optimization with precise revision cleanup and flexible scheduling.', 'rvg-optimize-database').'</em> </p>
        <span>Now maintained and supported by <a href="https://www.nerdpress.net/?utm_source=odadr&utm_medium=plugin&utm_campaign=odadr-header">NerdPress</a>! <a href="https://www.nerdpress.net/announcing-optimize-database/?utm_source=odadr&utm_medium=plugin&utm_campaign=odadr-header">Read the announcement</a>.
        </span>
      </div>
	  <!--odb-subheader-left-->
      <div class="odb-subheader-right"></div>
      <!-- odb-subheader-right -->
    </div>
    <!--odb-subheader-container-->
  </div>
  <!-- odb-options-opening -->
</div>
<!-- /odb-header -->
		';
	} // display_header


	/********************************************************************************************
	 *	DISPLAY THE CURRENT SETTINGS
	 ********************************************************************************************/
	function display_current_settings() {
		global $odb_class;

		$y = __('YES', 'rvg-optimize-database');
		$n = __('NO',  'rvg-optimize-database');

		// CURRENT SETTINGS
		$trash  = ($odb_class->odb_rvg_options['clear_trash']      == 'Y') ? $y : $n;
		$spam   = ($odb_class->odb_rvg_options['clear_spam']       == 'Y') ? $y : $n;
		$tag    = ($odb_class->odb_rvg_options['clear_tags']       == 'Y') ? $y : $n;

		if($odb_class->odb_rvg_options['clear_transients'] == 'Y') {
			$trans = __('DELETE EXPIRED TRANSIENTS', 'rvg-optimize-database');
		} else if ($odb_class->odb_rvg_options['clear_transients'] == 'A') {
			$trans = __('DELETE ALL TRANSIENTS', 'rvg-optimize-database');
		} else {
			$trans = $n;
		}

		//$trans  = ($odb_class->odb_rvg_options['clear_transients'] == 'Y') ? $y : $n;
		$ping   = ($odb_class->odb_rvg_options['clear_pingbacks']  == 'Y') ? $y : $n;
		$oembed = ($odb_class->odb_rvg_options['clear_oembed']     == 'Y') ? $y : $n;
		$log    = ($odb_class->odb_rvg_options['logging_on']       == 'Y') ? $y : $n;
		$innodb = ($odb_class->odb_rvg_options['optimize_innodb']  == 'Y') ? $y : $n;

		if($odb_class->odb_rvg_options['schedule_type'] == 'fiveminutes')
			$schedule = __('EVERY FIVE MINUTES','rvg-optimize-database');
		else if($odb_class->odb_rvg_options['schedule_type'] == 'hourly')
			$schedule = __('ONCE HOURLY','rvg-optimize-database');
		else if($odb_class->odb_rvg_options['schedule_type'] == 'twicedaily')
			$schedule = __('TWICE DAILY','rvg-optimize-database');
		else if($odb_class->odb_rvg_options['schedule_type'] == 'daily')
			$schedule = __('ONCE DAILY','rvg-optimize-database');
		else if($odb_class->odb_rvg_options['schedule_type'] == 'weekly')
			$schedule = __('ONCE WEEKLY','rvg-optimize-database');
		else if($odb_class->odb_rvg_options['schedule_type'] == 'monthly')
			$schedule = __('ONCE MONTHLY','rvg-optimize-database');
		else $schedule = __('NOT SCHEDULED','rvg-optimize-database');

		echo '
		<div id="odb-current-settings" class="odb-padding-left">
          <div class="odb-title-bar">
            <h2>'.__('Current settings','rvg-optimize-database').'</h2>
          </div>
		  <br><br>
		 ';

		// CUSTOM POST TYPES (from v4.4)
		$rel_posttypes = $odb_class->odb_rvg_options['post_types'];
		$rpt = '';
		foreach ($rel_posttypes as $posttype => $value) {
			if ($value == 'Y') {
				if ($rpt != '') $rpt .= ', ';
				$rpt .= strtoupper($posttype);
			} // if ($value == 'Y')
		} // foreach($rel_posttypes as $posttypes)

		if ($rpt == '') $rpt = '(' . __('NONE', 'rvg-optimize-database') . ')';

		echo '<span class="odb-bold">'.__('Delete revisions of', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$rpt.'</span><br>';

		 if($odb_class->odb_rvg_options['delete_older'] == 'Y') {
			 echo '<span class="odb-bold">'.__('Delete revisions older than', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$odb_class->odb_rvg_options['older_than'].' '.__("days", 'rvg-optimize-database').'</span><br>';
		 }

		 if($odb_class->odb_rvg_options['rvg_revisions'] == 'Y') {
			 echo '<span class="odb-bold">'.__('Maximum number of - most recent - revisions to keep per post / page', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$odb_class->odb_rvg_options['nr_of_revisions'].'</span><br>';
		 }

		 // v4.8.6
		 $lastrun = '';
		 if ($odb_class->odb_rvg_options['last_run_seconds'] == '') {
			 $lastrun = '<span class="odb-bold">' . __('Last run', 'rvg-optimize-database') . ':</span> <span class="odb-bold odb-blue">' . $odb_class->odb_rvg_options['last_run'] . '</span><br>';
		 } else {
			 $lastrun = '<span class="odb-bold">' . __('Last run', 'rvg-optimize-database') . ':</span> <span class="odb-bold odb-blue">' . $odb_class->odb_rvg_options['last_run'] . ' ' . __('hrs', 'rvg-optimize-database') . ' (' . __('in', 'rvg-optimize-database') . ' ' .$odb_class->odb_rvg_options['last_run_seconds'] . ' ' . __('seconds', 'rvg-optimize-database') . ')</span><br>';
		 }

		 echo '
		  <span class="odb-bold">'.__('Delete trashed items', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$trash.'</span><br>
		  <span class="odb-bold">'.__('Delete spammed items', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$spam.'</span><br>
		  <span class="odb-bold">'.__('Delete unused tags', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$tag.'</span><br>
		  <span class="odb-bold">'.__('Delete transients', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$trans.'</span><br>
		  <span class="odb-bold">'.__('Delete pingbacks and trackbacks', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$ping.'</span><br>
		  <span class="odb-bold">'.__('Clear oEmbed cache', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$oembed.'</span><br>
		  <span class="odb-bold">'.__('Keep a log', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$log.'</span><br>
		  <span class="odb-bold">'.__('Optimize InnoDB tables', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$innodb.'</span><br>
		  <span class="odb-bold">'.__('Number of excluded tables', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.count($odb_class->odb_rvg_excluded_tabs).'</span><br>
		  ' . $lastrun . '
		  <span class="odb-bold">' . __('Scheduler', 'rvg-optimize-database') . ':</span> <span class="odb-bold odb-blue">' . $schedule . '</span><br>
		';

		if($odb_class->odb_rvg_options['schedule_type'] != '') {
			// v4.5
			$current_timestamp = current_time('timestamp', 1);
			$cron_timestamp    = wp_next_scheduled('odb_scheduler');
			$diff_secs         = $cron_timestamp - $current_timestamp;
			$nextrun           = $this->secondsToTime($diff_secs) . '<br>';
			echo '
		  <span class="odb-bold">'.__('Next scheduled run','rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$nextrun.'</span><br>
			';
		} // if($odb_class->odb_rvg_options['schedule_type'] != '')

		echo '
		  <span class="odb-bold">'.__('Total savings since the first run', 'rvg-optimize-database').':</span> <span class="odb-bold odb-blue">'.$odb_class->odb_utilities_obj->odb_format_size($odb_class->odb_rvg_options['total_savings']).'</span>
		</div><!-- /odb-current-settings -->
		';
	} // display_current_settings()


	/********************************************************************************************
	 *	CONVERT SECONDS TO DAYS, HOURS, MINUTES AND SECONDS
	 ********************************************************************************************/
	private function secondsToTime($seconds) {
		global $odb_class;

		$dtF = new \DateTime('@0');
		$dtT = new \DateTime("@$seconds");
		// v4.5.2
		$d = __('days', 'rvg-optimize-database');
		$h = __('hours', 'rvg-optimize-database');
		$i = __('minutes', 'rvg-optimize-database');
		$a = __('and', 'rvg-optimize-database');
		$s = __('seconds', 'rvg-optimize-database');
		return $dtF->diff($dtT)->format('%a ' . $d . ', %h ' . $h . ', %i ' . $i . ' ' . $a . ' %s ' . $s);
	} // secondsToTime()


	/********************************************************************************************
	 *	DISPLAY THE START BUTTONS
	 ********************************************************************************************/
	function display_start_buttons($action) {
		global $odb_class;

		$nonce = wp_create_nonce( 'rvg-optimize-database' );

		if(!defined('RUN_OPTIMIZE_DATABASE')) {
			echo '
		<div id="odb-start-buttons" class="odb-padding-left">
		  <br>
		  <p>
		  <a href="' . esc_url( add_query_arg( array( 'page' => 'odb_settings_page' ), admin_url( 'options-general.php' ) ) ) . '">' . __( 'Change Settings', 'rvg-optimize-database' ) . '</a>
			';

			// v4.6
			if($odb_class->odb_logger_obj->odb_log_count() > 0) {
				// THERE IS A LOG FILE
				// v4.6.2
				$msg = str_replace("'", "\'", __('Clear the log?', 'rvg-optimize-database'));

				echo "
<script>
function odb_confirm_delete() {
	if(confirm('" . $msg . "')) {
		self.location = 'tools.php?page=rvg-optimize-database&action=clear_log&_wpnonce=<?php echo esc_js( $nonce ); ?>'
		return;
	}
} // odb_confirm_delete()
</script>
				";
			} // if(file_exists($this->odb_plugin_path.'logs/rvg-optimize-db-log.html'))
			else {
				echo '<br><br>';
			}

			if($action != 'run') {
				// NOT RUNNING: SHOW LOG- AND START BUTTONS
				if($odb_class->odb_logger_obj->odb_log_count() > 0) {
					echo '
					&nbsp;&nbsp;&nbsp;<a href="' . esc_url( add_query_arg( array( 'page' => 'rvg-optimize-database&action', 'action' => 'view_log', '_wpnonce' => $nonce, ), admin_url( 'tools.php' ) ) ) . '">' . __( 'View Log', 'rvg-optimize-database' ) . '</a>
		  <br><br>
					';
				} // if($odb_class->odb_logger_obj->odb_log_count() > 0)

				// v4.8.0
				echo '
          <input class="button-primary button-large" type="button" name="analyze_summary" value="'.__('Analyze (summary)', 'rvg-optimize-database').'" onclick="self.location=\'tools.php?page=rvg-optimize-database&action=analyze_summary&_wpnonce=' . $nonce . '\'" class="odb-bold">
          &nbsp;
		  <input class="button-primary button-large" type="button" name="analyze_detail" value="'.__('Analyze (detail)', 'rvg-optimize-database').'" onclick="self.location=\'tools.php?page=rvg-optimize-database&action=analyze_detail&_wpnonce=' . $nonce . '\'" class="odb-bold">
		  <br><br>
		  <input class="button-primary button-large" type="button" name="run_summary" value="'.__('Optimize (summary)', 'rvg-optimize-database').'" onclick="self.location=\'tools.php?page=rvg-optimize-database&action=run_summary&_wpnonce=' . $nonce . '\'" class="odb-bold">
		  &nbsp;
		  <input class="button-primary button-large" type="button" name="run_detail" value="'.__('Optimize (detail)', 'rvg-optimize-database').'" onclick="self.location=\'tools.php?page=rvg-optimize-database&action=run_detail&_wpnonce=' . $nonce . '\'" class="odb-bold">
				';
			} // if($odb_class->odb_logger_obj->odb_log_count() > 0)

			echo '
		  </p>
		</div><!-- /odb-start-buttons -->
			';
		} else if (RUN_OPTIMIZE_DATABASE) {
			echo 'Database optimized!';
		} // if(!defined('RUN_OPTIMIZE_DATABASE'))
	} // display_start_buttons()
} // ODB_Displayer
?>
