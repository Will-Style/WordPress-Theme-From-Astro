<?php
/************************************************************************************************
 *
 *	CLEANER CLASS: DOING THE REAL CLEANING / OPTIMIZATION
 *
 ************************************************************************************************/
?>
<?php
class ODB_Cleaner {
	var $start_size;
	var $log_arr;
	var $nr_of_optimized_tables;
	var $grand_total = 0;


	/********************************************************************************************
	 *
	 *	CONSTRUCTOR
	 *
	 ********************************************************************************************/
    function __construct() {
	} // __construct()


	/********************************************************************************************
	 *
	 *	RUN CLEANER
	 *
	 ********************************************************************************************/
	function odb_run_cleaner($scheduler, $action = 'run_detail') {
		global $odb_class;

		if(!$scheduler) {
			if ($action == 'analyze_summary' || $action == 'analyze_detail') {
				if ($action == 'analyze_summary') {
					$txt = 'Analyzing (summary)';
				} else if ($action == 'analyze_detail') {
					$txt = 'Analyzing (detail)';
				}
				echo '
		  <div id="odb-cleaner" class="odb-padding-left">
			<div class="odb-title-bar">
			  <h2>'.__($txt, 'rvg-optimize-database').'</h2>
			</div>
			<br>
			<br>
				';
			} else {
				echo '
		  <div id="odb-cleaner" class="odb-padding-left">
			<div class="odb-title-bar">
			  <h2>'.__('Cleaning Database', 'rvg-optimize-database').'</h2>
			</div>
			<br>
			<br>
				';
			}
		} // if(!$scheduler)

		// GET THE SIZE OF THE DATABASE BEFORE OPTIMIZATION
		$this->start_size = $odb_class->odb_utilities_obj->odb_get_db_size();

		// TIMESTAMP FOR LOG FILE - v4.6
		$ct = ($scheduler) ? ' (cron)' : '';

		$odb_class->log_arr["timestamp"]  = current_time('YmdHis', 0);
		$odb_class->log_arr["after"]      = 0;
		$odb_class->log_arr["before"]     = 0;
		$odb_class->log_arr["pingbacks"]  = 0;
		$odb_class->log_arr["oembeds"]    = 0;
		$odb_class->log_arr["orphans"]    = 0;
		$odb_class->log_arr["revisions"]  = 0;
		$odb_class->log_arr["savings"]    = 0;
		$odb_class->log_arr["spam"]       = 0;
		$odb_class->log_arr["tables"]     = 0;
		$odb_class->log_arr["tags"]       = 0;
		$odb_class->log_arr["transients"] = 0;
		$odb_class->log_arr["trash"]      = 0;

		/****************************************************************************************
		 *
		 *	DELETE REVISIONS
		 *
		 ****************************************************************************************/
		if($odb_class->odb_rvg_options['delete_older'] == 'Y' || $odb_class->odb_rvg_options['rvg_revisions'] == 'Y') {

			// FIND REVISIONS
			$results_older_than = array();
			if($odb_class->odb_rvg_options['delete_older'] == 'Y') {
				$results_older_than = $this->odb_get_revisions_older_than();
			} // if($odb_class->odb_rvg_options['delete_older'] == 'Y')

			$results_keep_revisions = array();
			if($odb_class->odb_rvg_options['rvg_revisions'] == 'Y') {
				$results_keep_revisions = $this->odb_get_revisions_keep_revisions();
			} // if($odb_class->odb_rvg_options['rvg_revisions'] == 'Y')

			if (count($results_older_than) > 0 || count($results_keep_revisions) > 0) {
				// REVISIONS FOUND
				$this->grand_total += count($results_older_than) + count($results_keep_revisions);

				if ($action == 'analyze_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('REVISIONS found to delete: ', 'rvg-optimize-database');?>
          <?php echo (count($results_older_than) + count($results_keep_revisions)) . '<br>';?>
		</div>
	<?php
				} // if ($action == 'analyze_summary')

				if ($action == 'run_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('REVISIONS deleted: ', 'rvg-optimize-database');?>
          <?php echo (count($results_older_than) + count($results_keep_revisions)) . '<br>';?>
		</div>
	<?php
				} // if ($action == 'run_summary')

				if ($action == 'analyze_detail' || $action == 'run_detail') {
					// 'ANALYZE_DETAIL', 'RUN_SUMMARY' or 'RUN_DETAIL'
					if ($action == 'analyze_detail') {
						$msg1 = __('REVISIONS', 'rvg-optimize-database');
						$msg2 = __('total number of revisions', 'rvg-optimize-database');
					} else {
						// 'RUN_SUMMARY' or 'RUN_DETAIL'
						if ($action == 'run_detail') {
							$msg1 = __('DELETED REVISIONS', 'rvg-optimize-database');
							$msg2 = __('total number of revisions deleted', 'rvg-optimize-database');
						} // if ($action == 'run_detail')
					} // if ($action == 'analyze_detail')
	?>
	<table border="0" cellspacing="8" cellpadding="2" class="odb-result-table">
	  <tr>
		<td colspan="4"><div class="odb-found">
			<?php echo $msg1 ?>
		  </div></td>
	  </tr>
	  <tr>
		<th align="right" class="odb-border-bottom">#</th>
		<th align="left" class="odb-border-bottom"><?php _e('prefix', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('post / page', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('revision date', 'rvg-optimize-database');?></th>
		<th align="right" class="odb-border-bottom"><?php echo $msg2?></th>
	  </tr>
	  <?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				// LOOP THROUGH THE REVISIONS AND DELETE THEM
				$total_deleted = $this->odb_delete_revisions($scheduler, $action);

				if ($action == 'analyze_detail' || $action == 'run_detail') {
?>
	  <tr>
		<td colspan="5" align="right" class="odb-border-top odb-bold"><?php echo $msg2 ?>: <?php echo $total_deleted?></td>
	  </tr>
    </table>
<?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				if ($scheduler) $odb_class->log_arr["revisions"] = $total_deleted;

				if ($action == 'run_summary' || $action == 'run_detail') {
					// NUMBER OF DELETED REVISIONS FOR LOG FILE
					$odb_class->log_arr["revisions"] = $total_deleted;
				} // if ($action == 'analyze_detail' || $action == 'run_summary' || $action == 'run_detail')
			} else {
				// NO REVISIONS FOUND
				if (!$scheduler) {
?>
	<div class="odb-not-found">
	  <?php _e('No REVISIONS found to delete', 'rvg-optimize-database');?>
	</div>
<?php
				} // if (!$scheduler)
			} // if (count($results) > 0)
		} // if($odb_class->odb_rvg_options['delete_older'] == 'Y' || $odb_class->odb_rvg_options['rvg_revisions'] == 'Y')


		/****************************************************************************************
		 *
		 *	DELETE TRASHED ITEMS
		 *
		 ****************************************************************************************/
		if ($odb_class->odb_rvg_options['clear_trash'] == 'Y') {
			$results = $this->odb_get_trash();
			if (count($results) > 0) {
				// TRASHED ITEMS FOUNE
				$this->grand_total += count($results);

				// TRASH FOUND
				if ($action == 'analyze_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('TRASHED ITEMS found to delete: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
				} // if ($action == 'analyze_summary')

				if ($action == 'run_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('TRASHED ITEMS deleted: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
				} // if ($action == 'run_summary')

				if ($action == 'analyze_detail' || $action == 'run_detail') {
					// 'RUN' or 'ANALYZE_DETAIL
					if ($action == 'analyze_detail') {
						$msg1 = __('TRASHED ITEMS', 'rvg-optimize-database');
						$msg2 = __('total number of trashed items', 'rvg-optimize-database');
					} else {
					 // ACTION = 'RUN_DETAIL'
						$msg1 = __('DELETED TRASHED ITEMS', 'rvg-optimize-database');
						$msg2 = __('total number of trashed items deleted', 'rvg-optimize-database');
					} // if ($action == 'analyze_detail')
	?>
	<table border="0" cellspacing="8" cellpadding="2" class="odb-result-table">
	  <tr>
		<td colspan="4"><div class="odb-found">
			<?php echo $msg1 ?>
		  </div></td>
	  </tr>
	  <tr>
		<th align="right" class="odb-border-bottom">#</th>
		<th align="left" class="odb-border-bottom"><?php _e('prefix', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('type', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('IP address / title', 'rvg-optimize-database');?></th>
		<th align="left" nowrap="nowrap" class="odb-border-bottom"><?php _e('date', 'rvg-optimize-database');?></th>
	  </tr>
	  <?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				// LOOP THROUGH THE TRASHED ITEMS AND DELETE THEM
				$total_deleted = $this->odb_delete_trash($results, $scheduler, $action);

				if ($scheduler) $odb_class->log_arr["trash"] = $total_deleted;

				if ($action == 'run_summary' || $action == 'run_detail') {
					// NUMBER OF DELETED TRASH FOR LOG FILE
					$odb_class->log_arr["trash"] = $total_deleted;
				} // if (!$scheduler && ($action == 'run_summary' || $action == 'run_detail'))

				if ($action == 'analyze_detail' || $action == 'run_detail') {
?>
    </table>
<?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')
			} else {
				// NO TRASH FOUND
				if (!$scheduler) {
?>
	<div class="odb-not-found">
	  <?php _e('No TRASHED ITEMS found to delete', 'rvg-optimize-database');?>
	</div>
<?php
				} // if (!$scheduler)
			} // if (count($results) > 0)
		} // if($odb_class->odb_rvg_options['clear_trash'] == 'Y')


		/****************************************************************************************
		 *
		 *	DELETE SPAMMED ITEMS
		 *
		 ****************************************************************************************/
		 if ($odb_class->odb_rvg_options['clear_spam'] == 'Y') {
			 $results = $this->odb_get_spam();

			if (count($results) > 0) {
				// SPAM FOUND
				$this->grand_total += count($results);

				if ($action == 'analyze_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('SPAMMED ITEMS found to delete: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
				} // if ($action == 'analyze_summary')

				if ($action == 'run_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('SPAMMED ITEMS deleted: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
				} // if ($action == 'run_summary')

				if ($action == 'analyze_detail' || $action == 'run_detail') {
					if ($action == 'analyze_detail') {
							$msg1 = __('SPAMMED ITEMS', 'rvg-optimize-database');
							$msg2 = __('total number of spammed items', 'rvg-optimize-database');
					} // if ($action == 'analyze_detail')

					if ($action == 'run_detail') {
							$msg1 = __('DELETED SPAMMED ITEMS', 'rvg-optimize-database');
							$msg2 = __('total number of spammed items deleted', 'rvg-optimize-database');
					} // if ($action == 'run_detail')
	?>
	<table border="0" cellspacing="8" cellpadding="2" class="odb-result-table">
	  <tr>
		<td colspan="4"><div class="odb-found">
			<?php echo $msg1 ?>
		  </div></td>
	  </tr>
	  <tr>
		<th align="right" class="odb-border-bottom">#</th>
		<th align="left" class="odb-border-bottom"><?php _e('prefix', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('comment author', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('comment author email', 'rvg-optimize-database');?></th>
		<th align="left" nowrap="nowrap" class="odb-border-bottom"><?php _e('comment date', 'rvg-optimize-database');?></th>
	  </tr>
	  <?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				// LOOP THROUGH THE SPAMMED ITEMS AND DELETE THEM
				$total_deleted = $this->odb_delete_spam($results, $scheduler, $action);

				if ($scheduler) $odb_class->log_arr["spam"] = $total_deleted;

				if ($action == 'run_summary' || $action == 'run_detail') {
					// NUMBER OF DELETED SPAM FOR LOG FILE
					$odb_class->log_arr["spam"] = $total_deleted;
				} // if (!$scheduler && ($action == 'run_summary' || $action == 'run_detail'))

				if ($action == 'analyze_detail' || $action == 'run_detail') {
?>
    </table>
<?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')
			} else {
				// NO SPAM FOUND
				if (!$scheduler) {
?>
	<div class="odb-not-found">
	  <?php _e('No SPAMMED ITEMS found to delete', 'rvg-optimize-database');?>
	</div>
<?php
				} // if (!$scheduler)
			} // if (count($results) > 0)
		} // if ($odb_class->odb_rvg_options['clear_spam'] == 'Y')


		/****************************************************************************************
		 *
		 *	DELETE UNUSED TAGS
		 *
		 ****************************************************************************************/
		if ($odb_class->odb_rvg_options['clear_tags'] == 'Y') {
			// GET UNUSED TAGS
			$results = $this->odb_get_unused_tags();

			if (count($results) > 0) {
				// UNUSED TAGS FOUND
				$this->grand_total += count($results);

				if ($action == 'analyze_summary' || $action == 'run_summary') {
					if ($action == 'analyze_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('UNUSED TAGS found to delete: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'analyze_summary')

					if ($action == 'run_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('UNUSED TAGS deleted: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'run_summary')
				} // if ($action == 'analyze_summary' || $action == 'run_summary')

				if ($action == 'analyze_detail' || $action == 'run_detail') {
					if ($action == 'analyze_detail') {
						$msg1 = __('UNUSED TAGS', 'rvg-optimize-database');
						$msg2 = __('total number of unused tags', 'rvg-optimize-database');
					} else {
						 // ACTION = 'RUN_detail'
						$msg1 = __('DELETED UNUSED TAGS', 'rvg-optimize-database');
						$msg2 = __('total number of unused tags deleted', 'rvg-optimize-database');
					} // if ($action == 'analyze_detail')
	?>
	<table border="0" cellspacing="8" cellpadding="2" class="odb-result-table">
	  <tr>
		<td colspan="4"><div class="odb-found">
			<?php echo $msg1 ?>
		  </div></td>
	  </tr>
	  <tr>
		<th align="right" class="odb-border-bottom">#</th>
		<th align="left" class="odb-border-bottom"><?php _e('prefix', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('tag', 'rvg-optimize-database');?></th>
	  </tr>
	  <?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				// LOOP THROUGH THE UNUSED TAGS AND DELETE THEM
				$total_deleted = $this->odb_delete_unused_tags($results, $scheduler, $action);

				if ($scheduler) $odb_class->log_arr["tags"] = $total_deleted;

				if ($action == 'run_summary' || $action == 'run_detail') {
					// NUMBER OF UNUSED TAGS FOR LOG FILE
					$odb_class->log_arr["tags"] = $total_deleted;
				} // if (!$scheduler && ($action == 'run_summary' || $action == 'run_detail'))

				if ($action == 'analyze_detail' || $action == 'run_detail') {
?>
    </table>
<?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')
			} else {
				// NO UNUSED TAGS FOUND
				if (!$scheduler) {
?>
	<div class="odb-not-found">
	  <?php _e('No UNUSED TAGS found to delete', 'rvg-optimize-database');?>
	</div>
<?php
				} // if (!$scheduler)
			} // if (count($results) > 0)
		} // if ($odb_class->odb_rvg_options['clear_tags'] == 'Y')


		/****************************************************************************************
		 *
		 *	DELETE TRANSIENTS
		 *
		 ****************************************************************************************/
		 if ($odb_class->odb_rvg_options['clear_transients'] !== 'N') {

			 if ($odb_class->odb_rvg_options['clear_transients'] == 'Y') {
				 $type   = 'EXPIRED';
				 $typeLc = 'expired';
				 $option = 'Y';
			 } else {
				 $type   = '';
				 $typeLc = '';
				 $option = 'A';
			 }

			 $results = $this->odb_get_transients($option);

			if (count($results) > 0) {
				// TRANSIENTS FOUND
				$this->grand_total += count($results);

				if ($action == 'analyze_summary' || $action == 'run_summary') {
					if ($action == 'analyze_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e($type . ' TRANSIENTS found to delete: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'analyze_summary')

					if ($action == 'run_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e($type . ' TRANSIENTS deleted: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'run_summary')
				} // if ($action == 'analyze_summary' || $action == 'run_summary')

				if ($action == 'analyze_detail' || $action == 'run_detail') {
					// 'RUN' or 'ANALYZE_DETAIL
					if ($action == 'analyze_detail') {
						$msg1 = __($type . ' TRANSIENTS', 'rvg-optimize-database');
						$msg2 = __('total number of ' . $typeLc . ' transients', 'rvg-optimize-database');
					} else {
						 // ACTION = 'RUN_DETAIL'
						$msg1 = __('DELETED ' . $type . ' TRANSIENTS', 'rvg-optimize-database');
						$msg2 = __('total number of ' . $typeLc . ' deleted', 'rvg-optimize-database');
					} // if ($action == 'analyze_detail')
	?>
	<table border="0" cellspacing="8" cellpadding="2" class="odb-result-table">
	  <tr>
		<td colspan="4"><div class="odb-found">
			<?php echo $msg1 ?>
		  </div></td>
	  </tr>
      <tr>
	    <th align="right" class="odb-border-bottom">#</th>
	    <th align="left" class="odb-border-bottom"><?php _e('prefix', 'rvg-optimize-database');?></th>
	    <th align="left" class="odb-border-bottom"><?php _e('option name', 'rvg-optimize-database');?></th>
      </tr>
	  <?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				// LOOP THROUGH THE TRANSIENTS AND DELETE THEM
				$total_deleted = $this->odb_delete_transients($results, $scheduler, $action, $option);

				if ($scheduler) $odb_class->log_arr["transients"] = $total_deleted;

				if ($action == 'run_summary' || $action == 'run_detail') {
					// NUMBER OF DELETED SPAM FOR LOG FILE
					$odb_class->log_arr["transients"] = $total_deleted;
				} // if (!$scheduler && ($action == 'run_summary' || $action == 'run_detail'))

				if ($action == 'analyze_detail' || $action == 'run_detail') {
?>
    </table>
<?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')
			} else {
				// NO TRANSIENTS FOUND
				if (!$scheduler) {
					if ($option == 'Y') {
						$msg = 'No EXPIRED TRANSIENTS found to delete';
					} else {
						$msg = 'No TRANSIENTS found to delete';
					} // if ($option == 'Y')
?>
	<div class="odb-not-found">
	  <?php _e($msg, 'rvg-optimize-database');?>
	</div>
<?php
				} // if (!$scheduler)
			} // if (count($results) > 0)
		} // if ($odb_class->odb_rvg_options['clear_transients'] !== 'N')


		/****************************************************************************************
		 *
		 *	DELETE PINGBACKS AND TRACKBACKS
		 *
		 ****************************************************************************************/
		if ($odb_class->odb_rvg_options['clear_pingbacks'] == 'Y') {
			// GET PINGBACKS AND TRACKBACKS
			$results = $this->odb_get_pingbacks();

			if (count($results) > 0) {
				// PINGBACKS AND/OR TRACKBACKS FOUND FOUND
				$this->grand_total += count($results);

				if ($action == 'analyze_summary' || $action == 'run_summary') {
					if ($action == 'analyze_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('PINGBACKS AND/OR TRACKBACKS found to delete: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'analyze_summary')

					if ($action == 'run_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('PINGBACKS AND/OR TRACKBACKS deleted: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'run_summary')
				} // if ($action == 'analyze_summary' || $action == 'run_summary')

				if ($action == 'analyze_detail' || $action == 'run_detail') {
					if ($action == 'analyze_detail') {
						$msg1 = __('PINGBACKS AND TRACKBACKS', 'rvg-optimize-database');
						$msg2 = __('total number of pingbacks and trackbacks', 'rvg-optimize-database');
					} else {
						 // ACTION = 'RUN'
						$msg1 = __('DELETED PINGBACKS AND TRACKBACKS', 'rvg-optimize-database');
						$msg2 = __('total number of pingbacks and trackbacks deleted', 'rvg-optimize-database');
					} // if ($action == 'analyze_detail')
	?>
	<table border="0" cellspacing="8" cellpadding="2" class="odb-result-table">
	  <tr>
		<td colspan="4"><div class="odb-found">
			<?php echo $msg1 ?>
		  </div></td>
	  </tr>
	  <tr>
		<th align="right" class="odb-border-bottom">#</th>
		<th align="left" class="odb-border-bottom"><?php _e('prefix', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('type', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('comment_author', 'rvg-optimize-database');?></th>
		<th align="left" nowrap="nowrap" class="odb-border-bottom"><?php _e('date', 'rvg-optimize-database');?></th>
	  </tr>
	  <?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				// LOOP THROUGH THE PINGBACKS AND TRACKBACKS AND DELETE THEM
				$total_deleted = $this->odb_delete_pingbacks($results, $scheduler, $action);

				if ($scheduler) $odb_class->log_arr["pingbacks"] = $total_deleted;

				if ($action == 'run_summary' || $action == 'run_detail') {
					// NUMBER OF DELETED PINGBACKS AND TRACKBACKS FOR LOG FILE
					$odb_class->log_arr["pingbacks"] = $total_deleted;
				} // if (!$scheduler && ($action == 'run_summary' || $action == 'run_detail'))

				if ($action == 'analyze_detail' || $action == 'run_detail') {
?>
    </table>
<?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

			} else {
				// NO PINGBACKS NOR TRACKBACKS FOUND
				if (!$scheduler) {
?>
	<div class="odb-not-found">
	  <?php _e('No PINGBACKS nor TRACKBACKS found to delete', 'rvg-optimize-database');?>
	</div>
<?php
				} // if (!$scheduler)
			} // if (count($results) > 0)
		} // if ($odb_class->odb_rvg_options['clear_pingbacks'] == 'Y')


		/****************************************************************************************
		 *
		 *	DELETE OEMBED CACHE
		 *
		 ****************************************************************************************/
		if ($odb_class->odb_rvg_options['clear_oembed'] == 'Y') {

			// GET OEMBED CACHE
			$results = $this->odb_get_oembed();

			if (count($results) > 0) {
				// OEMBED CACHE FOUND
				$this->grand_total += count($results);

				if ($action == 'analyze_summary' || $action == 'run_summary') {
					if ($action == 'analyze_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('OEMBED CACHE ITEMS found to delete: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'analyze_summary')

					if ($action == 'run_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('OEMBED CACHE ITEMS deleted: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'run_summary')
				} // if ($action == 'analyze_summary' || $action == 'run_summary')

				if ($action == 'analyze_detail' || $action == 'run_detail') {
					if ($action == 'analyze_detail') {
						$msg1 = __('OEMBED CACHE items', 'rvg-optimize-database');
						$msg2 = __('total number of oembed cache items', 'rvg-optimize-database');
					} else {
						 // ACTION = 'RUN'
						$msg1 = __('DELETED OEMBED CACHE ITEMS', 'rvg-optimize-database');
						$msg2 = __('total number of oembed cache items deleted', 'rvg-optimize-database');
					} // if ($action == 'analyze_detail')
	?>
	<table border="0" cellspacing="8" cellpadding="2" class="odb-result-table">
	  <tr>
		<td colspan="4"><div class="odb-found">
			<?php echo $msg1 ?>
		  </div></td>
	  </tr>
	  <tr>
		<th align="right" class="odb-border-bottom">#</th>
		<th align="left" class="odb-border-bottom"><?php _e('prefix', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('meta key', 'rvg-optimize-database');?></th>
		<th align="left" class="odb-border-bottom"><?php _e('meta value', 'rvg-optimize-database');?></th>
	  </tr>
	  <?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				// LOOP THROUGH THE OEMBEDS
				$total_deleted = $this->odb_delete_oembed($results, $scheduler, $action);

				if ($scheduler) $odb_class->log_arr["oembeds"] = $total_deleted;

				if ($action == 'run_summary' || $action == 'run_detail') {
					// NUMBER OF OEMBED CACHE ITEMS FOR LOG FILE
					$odb_class->log_arr["oembeds"] = $total_deleted;
				} // if (!$scheduler && ($action == 'run_summary' || $action == 'run_detail')

				if ($action == 'analyze_detail' || $action == 'run_detail') {
?>
    </table>
<?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')
			} else {
				// NO OEMBED CACHE ITEMS FOUND
				if (!$scheduler) {
?>
	<div class="odb-not-found">
	  <?php _e('No OEMBED CACHE ITEMS found to delete', 'rvg-optimize-database');?>
	</div>
<?php
				} // if (!$scheduler)
			} // if (count($results) > 0)
		} // if ($odb_class->odb_rvg_options['clear_oembed'] == 'Y')


		/****************************************************************************************
		 *
		 *	DELETE ORPHANS
		 *
		 ****************************************************************************************/
		if($odb_class->odb_rvg_options['clear_orphans'] == 'Y') {

			// POSTMETA AND MEDIA ORPHANS
			$results = $this->odb_get_orphans($scheduler);

			 if (count($results) > 0) {
				// ORPHANS FOUND
				$this->grand_total += count($results);

				if ($action == 'analyze_summary' || $action == 'run_summary') {
					if ($action == 'analyze_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('ORPHANS found to delete: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'analyze_summary')

					if ($action == 'run_summary') {
	?>
		<div class="odb-found-number">
		  <?php _e('ORPHANS deleted: ', 'rvg-optimize-database');?>
          <?php echo count($results) . '<br>';?>
		</div>
	<?php
					} // if ($action == 'run_summary')
				} // if ($action == 'analyze_summary' || $action == 'run_summary')

				if ($action == 'analyze_detail' || $action == 'run_detail') {

					if ($action == 'analyze_detail') {
						$msg1 = __('ORPHANS', 'rvg-optimize-database');
						$msg2 = __('total number of orphans', 'rvg-optimize-database');
					} else {
						// ACTION = 'RUN_DETAIL'
						$msg1 = __('DELETED ORPHANS', 'rvg-optimize-database');
						$msg2 = __('total number of orphans deleted', 'rvg-optimize-database');
					} // if ($action == 'analyze_detail')
	?>
	<table border="0" cellspacing="8" cellpadding="2" class="odb-result-table">
	  <tr>
		<td colspan="4"><div class="odb-found">
			<?php echo $msg1 ?>
		  </div></td>
	  </tr>
      <tr>
        <th align="right" class="odb-border-bottom">#</th>
        <th align="left" class="odb-border-bottom"><?php _e('prefix', 'rvg-optimize-database');?></th>
        <th align="left" class="odb-border-bottom"><?php _e('type', 'rvg-optimize-database');?></th>
        <th align="left" class="odb-border-bottom"><?php _e('id', 'rvg-optimize-database');?></th>
        <th align="left" class="odb-border-bottom"><?php _e('title', 'rvg-optimize-database');?></th>
        <th align="left" nowrap="nowrap" class="odb-border-bottom"><?php _e('modified', 'rvg-optimize-database');?></th>
        <th align="left" class="odb-border-bottom"><?php _e('taxonomy', 'rvg-optimize-database');?></th>
        <th align="left" class="odb-border-bottom"><?php _e('meta key', 'rvg-optimize-database');?></th>
        <th align="left" class="odb-border-bottom"><?php _e('meta value', 'rvg-optimize-database');?></th>
      </tr>
	  <?php
				} // if ($action == 'analyze_detail' || $action == 'run_detail')

				// LOOP THROUGH THE ORPHANS AND DELETE THEM
				$total_deleted = $this->odb_delete_orphans($results, $scheduler, $action);

				if ($scheduler) $odb_class->log_arr["orphans"] = $total_deleted;

				if ($action == 'run_summary' || $action == 'run_detail') {
					// NUMBER OF ORPHANS FOR LOG FILE
					$odb_class->log_arr["orphans"] = $total_deleted;
				} // if (!$scheduler && ($action == 'run_summary' || $action == 'run_detail')
?>
    </table>
<?php
			} else {
				// NO ORPHANS FOUND
				if (!$scheduler) {
?>
	<div class="odb-not-found">
	  <?php _e('No ORPHANS found to delete', 'rvg-optimize-database');?>
	</div>
<?php
				} // if (!$scheduler)
			} // if (count($results) > 0)
		} // if($odb_class->odb_rvg_options['clear_orphans'] == 'Y')
	?>
		<div class="odb-found-number">
		  <?php _e('TOTAL NUMBER OF ITEMS: ', 'rvg-optimize-database');?>
          <?php echo '<span class="odb-blue">' . $this->grand_total . '</span><br>';?>
		</div>
	<?php
	} // odb_run_cleaner()


	/********************************************************************************************
	 *
	 *	RUN OPTIMIZER
	 *
	 ********************************************************************************************/
	function odb_run_optimizer($scheduler, $action) {
		global $odb_class;

		if(!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
?>
	<div class="odb-optimizing-table" class="odb-padding-left">
	  <div class="odb-title-bar">
		<h2><?php _e('Optimizing Database Tables', 'rvg-optimize-database');?></h2>
	  </div>
	  <br>
	  <br>
	  <table border="0" cellspacing="8" cellpadding="2">
		<tr>
		  <th class="odb-border-bottom" align="right">#</th>
		  <th class="odb-border-bottom" align="left"><?php _e('table name', 'rvg-optimize-database');?></th>
		  <th class="odb-border-bottom" align="left"><?php _e('optimization result', 'rvg-optimize-database');?></th>
		  <th class="odb-border-bottom" align="left"><?php _e('engine', 'rvg-optimize-database');?></th>
		  <th class="odb-border-bottom" align="right"><?php _e('table rows', 'rvg-optimize-database');?></th>
		  <th class="odb-border-bottom" align="right"><?php _e('table size', 'rvg-optimize-database');?></th>
		</tr>
		<?php
		} // if(!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail'))

		# OPTIMIZE THE DATABASE TABLES
		$this->nr_of_optimized_tables = $this->odb_optimize_tables($scheduler, $action);

		if(!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
	?>
	  </table>
	</div><!-- /odb-optimizing-table -->
<?php
		} // if(!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail'))
	} // odb_run_optimizer()


	/********************************************************************************************
	 *
	 *	CALCULATE AND DISPLAY SAVINGS
	 *
	 ********************************************************************************************/
	function odb_savings($scheduler, $action) {
		global $odb_class;
		global $odb_logger_obj;

		// NUMBER OF TABLES
		$odb_class->log_arr["tables"] = $this->nr_of_optimized_tables;
		// DATABASE SIZE BEFORE OPTIMIZATION
		$odb_class->log_arr["before"] = $odb_class->odb_utilities_obj->odb_format_size($this->start_size,3);
		// DATABASE SIZE AFTER OPTIMIZATION
		$end_size = $odb_class->odb_utilities_obj->odb_get_db_size();
		$odb_class->log_arr["after"] = $odb_class->odb_utilities_obj->odb_format_size($end_size,3);
		// TOTAL SAVING
		$odb_class->log_arr["savings"] = $odb_class->odb_utilities_obj->odb_format_size(($this->start_size - $end_size),3);

		if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
			// WRITE RESULTS TO THE DATABASE - v4.6
			$odb_class->odb_logger_obj->odb_add_log($odb_class->log_arr);
		}

		$total_savings = $odb_class->odb_rvg_options['total_savings'];
		$total_savings += ($this->start_size - $end_size);
		$odb_class->odb_rvg_options['total_savings'] = $total_savings;

		$odb_class->odb_multisite_obj->odb_ms_update_option('odb_rvg_options', $odb_class->odb_rvg_options);

		if(!$scheduler) {
	?>
    <div id="odb-savings" class="odb-padding-left">
	  <div class="odb-title-bar">
		<h2><?php _e('Savings', 'rvg-optimize-database');?></h2>
	  </div>
	  <br>
	  <br>
	  <table border="0" cellspacing="8" cellpadding="2">
		<tr>
		  <th>&nbsp;</th>
		  <th class="odb-border-bottom"><?php _e('size of the database', 'rvg-optimize-database');?></th>
		</tr>
		<tr>
		  <td align="right"><?php _e('BEFORE optimization', 'rvg-optimize-database');?></td>
		  <td align="right" class="odb-bold"><?php echo $odb_class->odb_utilities_obj->odb_format_size($this->start_size,3); ?></td>
		</tr>
		<tr>
		  <td align="right"><?php _e('AFTER optimization', 'rvg-optimize-database');?></td>
		  <td align="right" class="odb-bold"><?php echo $odb_class->odb_utilities_obj->odb_format_size($end_size,3); ?></td>
		</tr>
		<tr>
		  <td align="right" class="odb-bold"><?php _e('SAVINGS THIS TIME', 'rvg-optimize-database');?></td>
		  <td align="right" class="odb-border-top odb-bold"><?php echo $odb_class->odb_utilities_obj->odb_format_size(($this->start_size - $end_size),3); ?></td>
		</tr>
		<tr>
		  <td align="right" class="odb-bold"><?php _e('TOTAL SAVINGS SINCE THE FIRST RUN', 'rvg-optimize-database');?></td>
		  <td align="right" class="odb-border-top odb-bold"><?php echo $odb_class->odb_utilities_obj->odb_format_size($total_savings,3); ?></td>
		</tr>
	  </table>
    </div><!-- /odb-savings -->
<?php
		} // if(!$scheduler)
	} // odb_savings()


	/********************************************************************************************
	 *
	 *	SHOW LOADING TIME
	 *
	 ********************************************************************************************/
	function odb_done($analyze = false) {
		global $odb_class;

		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$finish = $time;

		$total_time = round(($finish - $odb_class->odb_start_time), 4);
		?>
      <div id="odb-done" class="odb-padding-left">
		<div class="odb-title-bar">
		  <h2>
			<?php _e('DONE!', 'rvg-optimize-database');?>
		  </h2>
		</div>
		<br>
		<br>
<?php
		if (!$analyze) {
?>
		<span class="odb-padding-left"><?php _e('Optimization took', 'rvg-optimize-database')?>&nbsp;<strong><?php echo $total_time;?></strong>&nbsp;<?php _e('seconds', 'rvg-optimize-database')?>.</span>
<?php
		} // if ($analyze)
?>
		<?php
		// v4.5.1
		$odb_class->odb_last_run_seconds = $total_time;

		if($odb_class->odb_logger_obj->odb_log_count() > 0) {
		?>
<script>
function odb_confirm_delete() {
<?php
		$nonce = wp_create_nonce( 'rvg-optimize-database' );
		// v4.6.2
		$msg = str_replace("'", "\'", __('Clear the log?', 'rvg-optimize-database'));
?>
	if(confirm('<?php echo $msg?>')) {
		self.location = 'tools.php?page=rvg-optimize-database&action=clear_log&_wpnonce=<?php echo esc_attr( $nonce ); ?>'
		return;
	}
} // odb_confirm_delete()
</script>
		<br><br>
		&nbsp;
		<input class="button odb-normal" type="button" name="view_log" value="<?php _e('View Log', 'rvg-optimize-database');?>" onclick="self.location='tools.php?page=rvg-optimize-database&action=view_log&_wpnonce=<?php echo esc_attr( $nonce ); ?>'" />
		&nbsp;
		<input class="button odb-normal" type="button" name="clear_log" value="<?php _e('Clear Log', 'rvg-optimize-database');?>" onclick="return odb_confirm_delete();" />
		<?php
		} // if($odb_class->odb_logger_obj->odb_log_count() > 0)
?>
      </div><!-- /odb-done -->
<?php
	} // odb_done()


	/********************************************************************************************
	 *
	 *	GET REVISIONS (OLDER THAN x DAYS)
	 *
	 ********************************************************************************************/
	function odb_get_revisions_older_than() {
		global $odb_class, $wpdb;

		$res_arr = array();

		// CUSTOM POST TYPES (from v4.4)
		$rel_posttypes = $odb_class->odb_rvg_options['post_types'];
		$in = [];
		foreach ($rel_posttypes as $posttype => $value) {
			if ($value == 'Y') {
				$in[] = $wpdb->prepare( '%s', $posttype );
			} // if ($value == 'Y')
		} // foreach($rel_posttypes as $posttypes)

		$where = '';
		if( ! empty( $in ) ) {
			$where = " AND p2.`post_type` IN (" . implode( ', ', $in ) . ")";
		} else {
			// NO POST TYPES TO DELETE REVISIONS FOR... SKIP!
			return $res_arr;
		} // if($in != '')

		$older_than = $odb_class->odb_rvg_options['older_than'];

		$index = 0;

		// LOOP THROUGH THE SITES (IF MULTI SITE)
		for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			$sql = $wpdb->prepare("
					SELECT %s AS site,
					p1.`ID`,
					p1.`post_parent`,
					p1.`post_title`,
					p1.`post_modified`
					FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'posts' ) . " p1, " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'posts' ) . " p2
					WHERE p1.`post_type` = 'revision'
					AND p1.`post_parent` = p2.ID
						{$where}
					AND p1.`post_modified` < date_sub(now(), INTERVAL %d DAY)
				ORDER BY UCASE(p1.`post_title`)
				",
				$prefix,
				$older_than);

			//echo 'OLDER: '.$sql.'<br>';

			$res = $wpdb->get_results($sql, ARRAY_A);

			for($j=0; $j<count($res); $j++) {
				if(isset($res[$j]) && !$this->odb_post_is_excluded($res[$j]['post_parent'])) {
					$res_arr[$index] = $res[$j];
					$index++;
				} // if(isset($res[$j]) && !$this->odb_post_is_excluded($res[$j]['post_parent']))
			} // for($j=0; $j<count($res); $j++)

		} // for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++)

		return $res_arr;
	} // odb_get_revisions_older_than()


	/********************************************************************************************
	 *
	 *	GET REVISIONS (KEEP MAX NUMBER OF REVISIONS)
	 *
	 ********************************************************************************************/
	function odb_get_revisions_keep_revisions() {
		global $odb_class, $wpdb;

		$res_arr = array();

		// CUSTOM POST TYPES (from v4.4)
		$rel_posttypes = $odb_class->odb_rvg_options['post_types'];
		$in = [];
		foreach ($rel_posttypes as $posttype => $value) {
			if ($value == 'Y') {
				$in[] = $wpdb->prepare( '%s', $posttype );
			} // if ($value == 'Y')
		} // foreach($rel_posttypes as $posttypes)

		$where1 = '';
		if ( ! empty( $in ) ) {
			$where1 = " AND p2.`post_type` IN (" . implode( ', ', $in ) . ")";
		} else {
			// NO POST TYPES TO DELETE REVISIONS FOR... SKIP!
			return $res_arr;
		} // if($in != '')

		// MAX NUMBER OF REVISIONS TO KEEP
		$max_revisions = $odb_class->odb_rvg_options['nr_of_revisions'];

		$index = 0;

		// SKIP REVISIONS THAT WILL BE DELETED BY THE 'OLDER THAN' OPTION
		$where2 = '';
		if($odb_class->odb_rvg_options['delete_older'] == 'Y') {
			$older_than = $odb_class->odb_rvg_options['older_than'];
			$where2 = $wpdb->prepare( "AND p1.`post_modified` >= date_sub(now(), INTERVAL %d DAY)", $older_than );
		}

		for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			$sql = $wpdb->prepare("
			  SELECT %s AS site,
			  	p1.`ID`,
				p1.`post_parent`,
				p1.`post_title`,
				p1.`post_modified`,
				COUNT(*) cnt
				FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'posts' ) . " p1, " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'posts' ) . " p2
			   WHERE p1.`post_type` = 'revision'
                 AND p1.`post_parent` = p2.ID
                     {$where1}
					 {$where2}
			GROUP BY p1.`post_parent`
			  HAVING COUNT(*) > %d
			ORDER BY UCASE(p1.`post_title`)
			",
			$prefix,
			$max_revisions);

			//echo 'KEEP: '.$sql.'<br>';

			$res = $wpdb->get_results($sql, ARRAY_A);
			for($j=0; $j<count($res); $j++) {
				if(isset($res[$j]) && !$this->odb_post_is_excluded($res[$j]['post_parent'])) {
					$res_arr[$index] = $res[$j];
					$index++;
				}
			} // for($j=0; $j<count($res); $j++)
		} // for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++)

		return $res_arr;
	} // odb_get_revisions_keep_revisions()


	/********************************************************************************************^
	 *
	 *	DELETE THE REVISIONS
	 *
	 ********************************************************************************************/
	function odb_delete_revisions($scheduler, $action = 'run_detail') {
		global $odb_class, $wpdb;

		$total_deleted = 0;

		// v4.8.1
		if($scheduler && $action == '') {
			$action = 'run_detail';
		} // if($scheduler && $action == '')

		if($odb_class->odb_rvg_options['delete_older'] == 'Y') {
			// DELETE REVISIONS OLDER THAN x DAYS
			$results    = $this->odb_get_revisions_older_than();
			$older_than = $odb_class->odb_rvg_options['older_than'];
			$total_deleted += count($results);

			for($i = 0; $i < count($results); $i++) {
				if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
			?>
		<tr>
		  <td align="right" valign="top"><?php echo ($i + 1)?>.</td>
		  <td align="left" valign="top"><?php echo $results[$i]['site']?></td>
		  <td valign="top" class="odb-bold"><?php echo $results[$i]['post_title']?></td>
		  <td valign="top" class="odb-bold"><?php echo $results[$i]['post_modified']?></td><?php
				} // if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail'))

				if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
					$sql_delete = $wpdb->prepare("
					DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'posts' ) . "
					 WHERE `ID` = %d
					", $results[$i]['ID']);

					$wpdb->get_results($sql_delete);
				} // if ($action == 'run')

				if(!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
		?>
		  <td align="right" valign="top" class="odb-bold">1</td>
		</tr>
		<?php
				} // if(!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')
			} // for($i=0; $i<count($results); $i++)
		} // if($odb_class->odb_rvg_options['delete_older'] == 'Y')

		if($odb_class->odb_rvg_options['rvg_revisions'] == 'Y') {
			// KEEP MAX NUMBER OF REVISIONS
			$results       = $this->odb_get_revisions_keep_revisions();
			$max_revisions = $odb_class->odb_rvg_options['nr_of_revisions'];

			for($i = 0; $i < count($results); $i++) {
				$nr_to_delete  = $results[$i]['cnt'] - $max_revisions;
				$total_deleted += $nr_to_delete;

				if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
			?>
		<tr>
		  <td align="right" valign="top"><?php echo ($i + 1)?>.</td>
		  <td align="left" valign="top"><?php echo $results[$i]['site']?></td>
		  <td valign="top" class="odb-bold"><?php echo $results[$i]['post_title']?></td>
		  <td valign="top" class="odb-bold"><?php echo $results[$i]['post_modified']?></td><?php
				} // if (!$scheduler && $action == 'analyze_detail' || $action == 'run_detail'))

				$sql_get_posts = $wpdb->prepare( "
				  SELECT `ID`, `post_modified`
					FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'posts' ) . "
				   WHERE `post_parent` = %d
					 AND `post_type`   = 'revision'
				ORDER BY `post_modified` ASC
				", $results[$i]['post_parent']);

				$results_get_posts = $wpdb->get_results($sql_get_posts);

				for($j = 0; $j < $nr_to_delete; $j++) {
					// if(!$scheduler) echo $results_get_posts[$j]->post_modified.'<br>';
					if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
						$sql_delete = $wpdb->prepare("
						DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'posts' ) . "
						 WHERE `ID` = %d
						", $results_get_posts[$j]->ID);

						$wpdb->get_results($sql_delete);
					} // if ($action == 'run')
				} // for($j = 0; $j < $nr_to_delete; $j++)

				if(!$scheduler && ($action == 'analyze_detail' ||  $action == 'run_detail')) {
		?></td>
		  <td align="right" valign="top" class="odb-bold"><?php echo $nr_to_delete?> <?php _e('of', 'rvg-optimize-database')?> <?php echo $results[$i]['cnt'];?></td>
		</tr>
		<?php
				} // if(!$scheduler && ($action == 'analyze_detail' ||  $action == 'run_detail'))
			} // for($i = 0; $i < count($results); $i++)
		} // if($odb_class->odb_rvg_options['rvg_revisions'] == 'Y')

		return $total_deleted;
	} // function odb_delete_revisions()


	/********************************************************************************************
	 *
	 *	CHECK IF POST IS EXCLUDED BY A CUSTOM FIELD ('keep_revisions')
	 *
	 ********************************************************************************************/
	function odb_post_is_excluded($parent_id) {
		$keep_revisions = get_post_meta($parent_id, 'keep_revisions', true);
		return ($keep_revisions === 'Y');
	} // odb_post_is_exclude()


	/********************************************************************************************
	 *
	 *	GET TRASHED POSTS / PAGES AND COMMENTS
	 *
	 ********************************************************************************************/
	function odb_get_trash() {
		global $wpdb, $odb_class;

		$res_arr = array();

		$index = 0;
		// LOOP TROUGH SITES
		for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			$sql = $wpdb->prepare("
			   SELECT %s AS site,
			   	`ID` AS id,
				'post' AS post_type,
				`post_title` AS title,
				`post_modified` AS modified
				 FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'posts' ) . "
				WHERE `post_status` = 'trash'
			UNION ALL
			   SELECT %s AS site,
			   	`comment_ID` AS id,
				'comment' AS post_type,
				`comment_author_IP` AS title,
				`comment_date` AS modified
				 FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'comments' ) . "
				WHERE `comment_approved` = 'trash'
			 ORDER BY post_type, UCASE(title)
			", $prefix, $prefix);

			$res = $wpdb->get_results($sql, ARRAY_A);

			if($res != null) {
				$res_arr[$index] = $res[0];
				$index++;
			} // if($res != null)
		} // for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++)

		return $res_arr;
	} // odb_get_trash()


	/********************************************************************************************
	 *
	 *	DELETE TRASHED POSTS AND PAGES
	 *
	 ********************************************************************************************/
	function odb_delete_trash($results, $scheduler, $action) {
		global $wpdb, $odb_class;

		$total_deleted = count($results);

		for ($i = 0; $i < $total_deleted; $i++) {
			if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
	?>
	<tr>
	  <td align="right" valign="top"><?php echo ($i + 1); ?></td>
	  <td align="left" valign="top"><?php echo $results[$i]['site']?></td>
	  <td valign="top"><?php echo $results[$i]['post_type']; ?></td>
	  <td valign="top"><?php echo $results[$i]['title']; ?></td>
	  <td valign="top" nowrap="nowrap"><?php echo $results[$i]['modified']; ?></td>
	</tr>
	<?php
			} // if (!$scheduler && ($action == 'analyze_detail' || $action = 'run_detail'))

			if($results[$i]['post_type'] == 'comment') {
				// DELETE META DATA (IF ANY...)
				if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
					$sql_delete = $wpdb->prepare("
					DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'commentmeta' ) . "
					 WHERE `comment_id` = %d
					", $results[$i]['id']);
					$wpdb->get_results($sql_delete);
				} // if ($action == 'run_summary' || $action == 'run_detail')
			} // if($results[$i]['post_type'] == 'comment')

			if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
				// DELETE TRASHED POSTS / PAGES
				$sql_delete = $wpdb->prepare("
				DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'posts' ) . "
				 WHERE `post_status` = %s
				", 'trash');
				$wpdb->get_results($sql_delete);

				// DELETE TRASHED COMMENTS
				$sql_delete = $wpdb->prepare("
				DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'comments' ) . "
				 WHERE `comment_approved` = %s
				", 'trash');
				$wpdb->get_results($sql_delete);
			} // if ($action == 'run_summary' || $action == 'run_detail')
		} // for ($i = 0; $i < $total_deleted; $i++)

		return $total_deleted;
	} // odb_delete_trash()


	/********************************************************************************************
	 *
	 *	GET SPAMMED COMMENTS
	 *
	 ********************************************************************************************/
	function odb_get_spam() {
		global $wpdb, $odb_class;

		$res_arr = array();

		// LOOP THROUGH SITES
		for ($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			$sql = $wpdb->prepare("
			  SELECT %s AS site,
			  	`comment_ID`,
				`comment_author`,
				`comment_author_email`,
				`comment_date`
				FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'comments' ) . "
			   WHERE `comment_approved` = 'spam'
			ORDER BY UCASE(`comment_author`)
			", $prefix);

			$res = $wpdb->get_results($sql, ARRAY_A);

			for($j = 0; $j < count($res); $j++) {
				array_push($res_arr, $res[$j]);
			} // for($j = 0; $j < count($res); $j++)
		} // for ($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++)
		return $res_arr;
	} // odb_get_spam()


	/********************************************************************************************
	 *
	 *	DELETE SPAMMED ITEMS
	 *
	 ********************************************************************************************/
	function odb_delete_spam($results, $scheduler, $action = 'run') {
		global $wpdb, $odb_class;

		$total_deleted = count($results);

		for ($i = 0; $i < count($results); $i++) {
			if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
	?>
	<tr>
	  <td align="right" valign="top"><?php echo ($i + 1); ?></td>
	  <td align="left" valign="top"><?php echo $results[$i]['site']?></td>
	  <td valign="top"><?php echo $results[$i]['comment_author']; ?></td>
	  <td valign="top"><?php echo $results[$i]['comment_author_email']; ?></td>
	  <td valign="top" nowrap="nowrap"><?php echo $results[$i]['comment_date']; ?></td>
	</tr>
	<?php
			} // if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail'))

			if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
				$sql_delete = $wpdb->prepare("
				DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'commentmeta' ) . "
				 WHERE `comment_id` = %d
				", $results[$i]['comment_ID']);
				$wpdb->get_results($sql_delete);

				$sql_delete = $wpdb->prepare("
				DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'comments' ) . "
				 WHERE `comment_approved` = %s
				", 'spam');
				$wpdb->get_results($sql_delete);
			} // if ($action == 'run_summary' || $action == 'run_detail')
		} // for ($i = 0; $i < count($results); $i++)

		return $total_deleted;
	} // odb_delete_spam()


	/********************************************************************************************
	 *
	 *	GET UNUSED TAGS
	 *
	 ********************************************************************************************/
	 function odb_get_unused_tags() {
		 global $wpdb, $odb_class;

		 $res_arr = array();

		// LOOP THROUGH SITES
		for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			$sql = $wpdb->prepare("
			SELECT %s AS site,
				a.term_id AS term_id, a.name AS name
			  FROM `" . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'terms' ) . "` a, `" . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'term_taxonomy' ) . "` b
			 WHERE a.term_id = b.term_id
			   AND b.taxonomy = 'post_tag'
			   AND b.term_taxonomy_id NOT IN (
				SELECT term_taxonomy_id
				  FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'term_relationships' ) . "
			    )
			ORDER BY name
			", $prefix);

			$res = $wpdb->get_results($sql, ARRAY_A);

			for($j = 0; $j < count($res); $j++) {
				array_push($res_arr, $res[$j]);
			} // for($j = 0; $j < count($res); $j++)
		} // for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++)
		return $res_arr;
	 } // odb_get_unused_tags


	/********************************************************************************************
	 *
	 *	DELETE UNUSED TAGS
	 *
	 ********************************************************************************************/
	function odb_delete_unused_tags($results, $scheduler, $action = 'run') {
		global $wpdb, $odb_class;

		$total_deleted = count($results);

		for ($i = 0; $i < $total_deleted; $i++) {
			if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
	?>
	<tr>
	  <td align="right" valign="top"><?php echo ($i + 1); ?></td>
	  <td align="left" valign="top"><?php echo $results[$i]['site']?></td>
	  <td valign="top"><?php echo $results[$i]['name']; ?></td>
	</tr>
	<?php
			} // if (!$scheduler && ($action = 'analyze_detail' || $action == 'run_detail'))

			if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
				$sql_del = $wpdb->prepare("
				DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'term_taxonomy' ) . "
				 WHERE term_id = %d
				", $results[$i]['term_id']);
				$wpdb->get_results($sql_del);

				$sql_del = $wpdb->prepare("
				DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'terms' ) . "
				 WHERE term_id = %d
				", $results[$i]['term_id']);
				$wpdb->get_results($sql_del);
			} // if ($action == 'run_summary' || $action == 'run_detail')
		} // for ($i = 0; $i < $total_deleted; $i++)

		return $total_deleted;
	} // odb_delete_unused_tags()


	/********************************************************************************************
	 *
	 *	IS THE UNUSED TAG USED IN ONE OR MORE SCHEDULED POSTS?
	 *
	 ********************************************************************************************/
	function odb_delete_tags_is_scheduled($term_id, $odb_prefix) {
		global $wpdb, $odb_class;

		$sql_get_posts = $wpdb->prepare("
		SELECT p.post_status
		  FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $odb_prefix . 'term_relationships' ) . " t, " . $odb_class->odb_utilities_obj->odb_sanitize_key( $odb_prefix . 'posts' ) . " p
		 WHERE t.term_taxonomy_id = %s
		   AND t.object_id        = p.ID
		", $term_id);

		$results_get_posts = $wpdb->get_results($sql_get_posts);
		for($i=0; $i<count($results_get_posts); $i++)
			if($results_get_posts[$i]->post_status == 'future') return true;

		return false;
	} // odb_delete_tags_is_scheduled()


	/********************************************************************************************
	 *
	 *	GET TRANSIENTS v4.8.2
	 *
	 ********************************************************************************************/
	 function odb_get_transients($option = "Y") {
	 	// $option == "A": delete all transients
	 	// %option == "Y": only delete EXPIRED transients

		global $wpdb, $odb_class;

		$res_arr = array();

		// ONE MINUTE DELAY
		$delay = time() - 60;

		// LOOP THROUGH SITES
		for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			$table = $prefix . 'options';
			// v4.8.2
			if ($option == 'Y') {
				// EXPIRED ONLY
				$sql = $wpdb->prepare("
					SELECT %s AS site,
						`option_name`
					FROM `" . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'options' ) . "`
					WHERE (
						option_name LIKE '_transient_timeout_%%'
						OR option_name LIKE '_site_transient_timeout_%%'
					)
					AND option_value < %d
					ORDER BY `option_name`
				", $prefix, $delay);
				//echo $sql . '<br>';
			} else {
				// ALL
				$sql = $wpdb->prepare("
					SELECT %s AS site,
						`option_name`
					FROM `" . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'options' ) . "`
					WHERE (
						option_name LIKE '_transient_timeout_%%'
						OR option_name LIKE '_site_transient_timeout_%%'
					)
					ORDER BY `option_name`
				", $prefix );
			}
			//echo $sql . '<br>';
			$res = $wpdb->get_results($sql, ARRAY_A);

			for($j = 0; $j < count($res); $j++) {
				array_push($res_arr, $res[$j]);
			} // for($j = 0; $j < count($res); $j++)
		} // for($i=0; $i<count($odb_class->odb_ms_prefixes); $i++)
		return $res_arr;
	 } // function odb_get_transients()


	/********************************************************************************************
	 *
	 *	DELETE TRANSIENTS (v4.8.2)
	 *
	 ********************************************************************************************/
	function odb_delete_transients($results, $scheduler, $action = 'run', $option = 'Y') {
		global $wpdb, $odb_class;

		// ONE MINUTE DELAY
		$delay = time() - MINUTE_IN_SECONDS;

		$total_deleted = count($results);

		// LOOP THROUGH SITES
		for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			for ($j = 0; $j < count($results); $j++) {
				if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
	?>
	<tr>
	  <td align="right" valign="top"><?php echo ($j + 1); ?></td>
	  <td align="left" valign="top"><?php echo $results[$j]['site']?></td>
	  <td valign="top"><?php echo $results[$j]['option_name']; ?></td>
	</tr>
	<?php
				} // if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail'))

				// v4.8.2
				if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
					$timeout = $results[$j]['option_name'];
					$trans = str_replace('_timeout', '', $timeout);
					$sqldel = $wpdb->prepare("
					DELETE FROM `" . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'options' ) . "`
					WHERE (
						option_name = %s
						OR option_name = %s
					)
					", $timeout, $trans);

					//echo $sqldel . '<br>';
					$resdel = $wpdb->get_results($sqldel, ARRAY_A);
				} // if ($action == 'run_summary' || $action == 'run_detail'))
			} // for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++)
		} // for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++)

		return $total_deleted;
	} // odb_delete_transients()


	/********************************************************************************************
	 *
	 *	GET PINGBACKS AND TRACKBACKS
	 *
	 ********************************************************************************************/
	function odb_get_pingbacks() {

		global $wpdb, $odb_class;

		$res_arr = array();

		// LOOP THROUGH SITES
		for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];;

			$sql = $wpdb->prepare("
			SELECT %s AS site,
				`comment_ID`,
				`comment_type`,
				`comment_author`,
				`comment_date`
			FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'comments' ) . "
			WHERE (
				`comment_type` = 'pingback' OR `comment_type` = 'trackback'
			)
			ORDER BY `comment_type`, `comment_author`
			", $prefix);

			$res = $wpdb->get_results($sql, ARRAY_A);

			for($j = 0; $j < count($res); $j++) {
				array_push($res_arr, $res[$j]);
			} // for($j = 0; $j < count($res); $j++)
		} // for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++)
		return $res_arr;
	} // odb_get_pingbacks


	/********************************************************************************************
	 *
	 *	DELETE PINGBACKS AND TRACKBACKS
	 *
	 ********************************************************************************************/
	function odb_delete_pingbacks ($results, $scheduler, $action = 'run') {
		global $wpdb, $odb_class;

		$total_deleted = count($results);

		for ($i = 0; $i < count($results); $i++) {
			if (!$scheduler && ($action == 'analyze_detail' | $action == 'run_detail')) {
	?>
	<tr>
	  <td align="right" valign="top"><?php echo ($i + 1); ?></td>
	  <td align="left" valign="top"><?php echo $results[$i]['site']?></td>
	  <td valign="top"><?php echo $results[$i]['comment_type']?></td>
	  <td valign="top"><?php echo $results[$i]['comment_author']?></td>
	  <td valign="top" nowrap="nowrap"><?php echo $results[$i]['comment_date']; ?></td>
	</tr>
	<?php
			} // if (!$scheduler && ($action == 'analyze_detail' | $action == 'run_detail'))

			if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
				for($j = 0; $j < count($results); $j++) {
					// DELETE METADATA FOR THIS COMMENT (IF ANY)
					$sql = $wpdb->prepare("
					DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$j]['site'] . 'commentmeta' ) . "
					 WHERE `comment_id` = %d
					", $results[$j]['comment_ID']);
					$wpdb->get_results($sql);

					$sql = $wpdb->prepare("
					DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$j]['site'] . 'comments' ) . "
					WHERE (
						`comment_type` = %s
						OR `comment_type` = %s
					)
					", 'pingback', 'trackback');
					$wpdb->get_results($sql);
				} // for($j = 0; $j < count($results); $j++)
			} // if ($action == 'run_summary' || $action == 'run_detail')
		} // for ($i = 0; $i < count($results); $i++)

		return $total_deleted;
	} // odb_delete_pingbacks()


	/********************************************************************************************
	 *
	 *	GET OEMBED CACHE
	 *
	 ********************************************************************************************/
	function odb_get_oembed() {
		global $wpdb, $odb_class;

		$res_arr = array();

		// LOOP THROUGH SITES
		for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			$sql = $wpdb->prepare("
			SELECT %s AS site,
				`meta_id`,
				`meta_key`,
				`meta_value`
			FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'postmeta' ) . "
			WHERE `meta_key` LIKE '_oembed_%%'
			ORDER BY `meta_key`
			", $prefix);

			$res = $wpdb->get_results($sql, ARRAY_A);

			for($j = 0; $j < count($res); $j++) {
				array_push($res_arr, $res[$j]);
			} // for($j = 0; $j < count($res); $j++)
		} // for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++)
		return $res_arr;
	} // odb_get_oembed()


	/********************************************************************************************
	 *
	 *	CLEAR OEMBED CACHE
	 *
	 ********************************************************************************************/
	function odb_delete_oembed($results, $scheduler, $action = 'run') {
		global $wpdb, $odb_class;

		$total_deleted = count($results);

		for($i = 0; $i < $total_deleted; $i++) {
			if (!$scheduler && ($action == 'analyze_detail' | $action == 'run_detail')) {
	?>
	<tr>
	  <td align="right" valign="top"><?php echo $i + 1; ?></td>
	  <td align="left" valign="top"><?php echo esc_html( $results[$i]['site'] ); ?></td>
	  <td valign="top"><?php echo esc_html( $results[$i]['meta_key'] ); ?></td>
	  <td valign="top"><?php echo esc_html( $results[$i]['meta_value'] ); ?></td>
	</tr>
	<?php
			} // if (!$scheduler && ($action == 'analyze_detail' | $action == 'run_detail'))

			if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
				// DELETE COMMENTS
				$wpdb->get_results("
				DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$i]['site'] . 'postmeta' ) . "
				WHERE `meta_key` LIKE '_oembed_%%'
				");
			} // if ($action == 'run_summary' || $action == 'run_detail')
		} // for($i = 0; $i < $total_deleted; $i++)

		return $total_deleted;
	} // odb_delete_oembed()


	/********************************************************************************************
	 *
	 *	GET ORPHAN POSTMETA AND MEDIA RECORDS
	 *
	 ********************************************************************************************/
	function odb_get_orphans() {
		global $wpdb, $odb_class;

		$res_arr = array();

		// LOOP THROUGH SITES
		for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++) {
			$prefix = $odb_class->odb_ms_prefixes[$i];

			$sql = $wpdb->prepare("
			SELECT %s AS site,
				`ID`,
				'post' AS type,
				`post_title`,
				`post_modified`,
				 '' AS term_taxonomy_id,
				 '' AS meta_key,
				 '' AS meta_value
			  FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'posts' ) . "
			 WHERE ID NOT IN (SELECT post_id FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'postmeta' ) . ")
			   AND post_status = 'auto-draft'
			 ORDER BY `ID`
			", $prefix);

			$results = $wpdb->get_results($sql, ARRAY_A);
			for ($j = 0; $j < count($results); $j++) {
				array_push($res_arr, $results[$j]);
			} // for ($j = 0; $j < count($results); $j++)

			// FIND POSTMETA ORPHANS
			$sql = $wpdb->prepare("
			SELECT %s AS site,
				`post_id` AS ID,
				'post meta' AS type,
				'' AS post_title,
				'' AS post_modified,
				'' AS term_taxonomy_id,
				`meta_key`,
				`meta_value`
			  FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'postmeta' ) . "
			 WHERE post_id NOT IN (SELECT ID FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $prefix . 'posts' ) . ")
			 ORDER BY `meta_key`
			", $prefix);
			//echo $sql . '<br>';

			$results = $wpdb->get_results($sql, ARRAY_A);
			for ($j = 0; $j < count($results); $j++) {
				array_push($res_arr, $results[$j]);
			} // for ($j = 0; $j < count($results); $j++)

			$results = $wpdb->get_results($sql, ARRAY_A);
			for ($j = 0; $j < count($results); $j++) {
				array_push($res_arr, $results[$j]);
			} // for ($j = 0; $j < count($results); $j++)
		} // for($i = 0; $i < count($odb_class->odb_ms_prefixes); $i++)
		return $res_arr;
	} // odb_get_orphans()


	/********************************************************************************************
	 *
	 *	DELETE ORPHAN POSTMETA AND MEDIA RECORDS
	 *
	 ********************************************************************************************/
	function odb_delete_orphans($results, $scheduler, $action = 'run_detail') {

		global $wpdb, $odb_class;

		$total_deleted = count($results);

		for($i = 0; $i < count($results); $i++) {
			if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
	?>
	<tr>
	  <td valign="top" align="right"><?php echo ($i + 1); ?></td>
	  <td valign="top" align="left"><?php echo $results[$i]['site']?></td>
	  <td valign="top"><?php echo $results[$i]['type']?></td>
	  <td valign="top"><?php echo $results[$i]['ID']?></td>
	  <td valign="top"><?php echo $results[$i]['post_title']?></td>
	  <td valign="top" nowrap="nowrap"><?php echo substr($results[$i]['post_modified'], 0, 10); ?></td>
      <td valign="top" align="left"><?php echo $results[$i]['term_taxonomy_id']?></td>
      <td valign="top" nowrap="nowrap"><?php echo $results[$i]['meta_key']; ?></td>
      <td valign="top" nowrap="nowrap"><?php echo $results[$i]['meta_value']; ?></td>
	</tr>
	<?php
			} // if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail'))

			if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
				$sql = "";
				for($j = 0; $j < count($results); $j++) {
					// DELETE METADATA FOR THIS COMMENT (IF ANY)
					if ($results[$j]['type'] == 'post meta') {
						$sql = $wpdb->prepare("
						DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$j]['site'] . 'postmeta' ) . "
						 WHERE `post_id` = %d
						", $results[$j]['ID']);
					} else if ($results[$j]['type'] == 'post') {
						$sql = $wpdb->prepare("
						DELETE FROM " . $odb_class->odb_utilities_obj->odb_sanitize_key( $results[$j]['site'] . 'posts' ) . "
						 WHERE `ID` = %d
						", $results[$j]['ID']);
					} // for($j = 0; $j < count($results); $j++)
					$wpdb->get_results($sql);
				} // for($j = 0; $j < count($results); $j++)
			} // if ($action == 'run_summary' || $action == 'run_detail')
		} // for($i = 0; $i < count($results); $i++)
		return $total_deleted;
	} // odb_delete_orphans()


	/********************************************************************************************
	 *
	 *	OPTIMIZE DATABASE TABLES
	 *
	 ********************************************************************************************/
	function odb_optimize_tables($scheduler, $action) {
		global $odb_class, $wpdb;

		$cnt = 0;
		for ($i = 0; $i < count($odb_class->odb_tables); $i++) {
			if(!isset($odb_class->odb_rvg_excluded_tabs[$odb_class->odb_tables[$i][0]])) {
				# TABLE NOT EXCLUDED
				$cnt++;

				$sql = $wpdb->prepare("
				SELECT ENGINE, (data_length + index_length) AS size, TABLE_ROWS
				  FROM information_schema.TABLES
				 WHERE table_schema = %s
				   AND table_name   = %s
				", DB_NAME, $odb_class->odb_tables[$i][0]);
				//echo $sql.'<br>';
				$table_info = $wpdb->get_results($sql);
				//print_r($table_info);

				if($odb_class->odb_rvg_options["optimize_innodb"] == 'N' && strtolower($table_info[0]->ENGINE) == 'innodb') {
					// SKIP InnoDB tables
					$msg = __('InnoDB table: skipped...', 'rvg-optimize-database');
				} else {
					// v4.6.3
					if (@strtolower($table_info[0]->ENGINE) == 'myisam') {
						$result = $this->odb_optimize_myisam($odb_class->odb_tables[$i][0]);
						$msg    = $result[0]->Msg_text;
						if ($msg == 'OK') {
							$msg = __('<span class="odb-optimized">TABLE OPTIMIZED</span>', 'rvg-optimize-database');
						} else if ($msg == 'Table is already up to date') {
							$msg = __('Table is already up to date', 'rvg-optimize-database');
						}
					} else {
						$result = $this->odb_optimize_innodb($odb_class->odb_tables[$i][0]);
						$msg    = $result[0]->Msg_text;
						if ($msg == 'Table is already up to date') {
							$msg = __('Table is already up to date', 'rvg-optimize-database');
						} else {
							$msg = __('<span class="odb-optimized">TABLE OPTIMIZED</span>', 'rvg-optimize-database');
						}
					} // if (strtolower($table_info[0]->ENGINE) == 'myisam')
				} // if($odb_class->odb_rvg_options["optimize_innodb"] == 'N' && strtolower($table_info[0]->ENGINE) == 'innodb')

				if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail')) {
	?>
	<tr>
	  <td align="right" valign="top"><?php echo $cnt?>.</td>
	  <td valign="top" class="odb-bold"><?php echo $odb_class->odb_tables[$i][0] ?></td>
	  <td valign="top"><?php echo $msg ?></td>
	  <td valign="top"><?php echo $table_info[0]->ENGINE ?></td>
	  <td align="right" valign="top"><?php echo $table_info[0]->TABLE_ROWS ?></td>
	  <td align="right" valign="top"><?php echo $odb_class->odb_utilities_obj->odb_format_size($table_info[0]->size) ?></td>
	</tr>
	<?php
				} // if (!$scheduler && ($action == 'analyze_detail' || $action == 'run_detail'))
			} // if(!$excluded)
		} // for ($i = 0; $i < count($odb_class->odb_tables); $i++)
		return $cnt;
	} // odb_optimize_tables()


	/********************************************************************************************
	 *
	 *	OPTIMIZE A MyISAM TABLE
	 *
	 ********************************************************************************************/
	function odb_optimize_myisam($table_name) {
		global $wpdb;
		$query  = "OPTIMIZE TABLE " . $table_name;
		return $wpdb->get_results($query);
	} // odb_optimize_myisam()


	/********************************************************************************************
	 *
	 *	OPTIMIZE AN InnoDB TABLE
	 *
	 ********************************************************************************************/
	function odb_optimize_innodb($table_name) {
		global $wpdb;

		$query  = "OPTIMIZE TABLE " . $table_name;
		return $wpdb->get_results($query);

/*		// https://www.percona.com/blog/2010/12/09/mysql-optimize-tables-innodb-stop/
		$query = "SHOW KEYS FROM " . $table_name . " WHERE Key_name <> 'PRIMARY'";
		$result = $wpdb->get_results($query);
		if (count($result) > 0) {
			for ($i = 0; $i < count($result); $i++) {
				$key_name = $result[$i]->Key_name;

				$query = "ALTER TABLE " . $table_name . " DROP KEY " . $key_name;
				$result = $wpdb->get_results($query);

				$query = "ALTER TABLE " . $table_name . " add key(" . $key_name . ")";
				$result = $wpdb->get_results($query);
			} // for ($i = 0; $i < count($result); $i++)
			return __('<span class="odb-optimized">TABLE OPTIMIZED</span>', 'rvg-optimize-database');
		} else {
			return __('Table is already up to date', 'rvg-optimize-database');
		} // if (count($result) > 0*/
	} // odb_optimize_innodb()

} // ODB_Cleaner
?>