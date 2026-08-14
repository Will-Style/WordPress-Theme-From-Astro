<?php
/************************************************************************************************
 *
 *	LOGGER CLASS
 *
 ************************************************************************************************/
?>
<?php
class ODB_Logger {

	var $sql = '';
	var $res = array();

	/********************************************************************************************
	 *	CONSTRUCTOR
	 ********************************************************************************************/
    function __construct() {
	} // __construct()


	/********************************************************************************************
	 *	WRITE RESULTS TO LOG TABLE - v4.6
	 ********************************************************************************************/
	function odb_add_log() {
		global $odb_class, $wpdb;

		// IS LOGGING ENABLED?
		if($odb_class->odb_rvg_options['logging_on'] == "Y") {

			// CONVERT A TIMESTAMP TO THE mm/dd/yyyy hh:mm:ss format
			$d = $odb_class->odb_utilities_obj->odb_parse_timestamp($odb_class->log_arr['timestamp']);

			$this->sql = "
			INSERT INTO `" . $odb_class->odb_logtable_name . "`
				(
					odb_timestamp,
					odb_revisions,
					odb_trash,
					odb_spam,
					odb_tags,
					odb_transients,
					odb_pingbacks,
					odb_oembeds,
					odb_orphans,
					odb_tables,
					odb_before,
					odb_after,
					odb_savings
				 )
				VALUES
				(
					'" . $d . "',
					". $odb_class->log_arr['revisions'] . ",
					". $odb_class->log_arr['trash'] . ",
					". $odb_class->log_arr['spam'] . ",
					". $odb_class->log_arr['tags'] . ",
					". $odb_class->log_arr['transients'] . ",
					". $odb_class->log_arr['pingbacks'] . ",
					". $odb_class->log_arr['oembeds'] . ",
					". $odb_class->log_arr['orphans'] . ",
					". $odb_class->log_arr['tables'] . ",
					'". $odb_class->log_arr['before'] . "',
					'". $odb_class->log_arr['after'] . "',
					'". $odb_class->log_arr['savings'] . "'
				)
			";
			$wpdb->get_results($this->sql);
		} // if($odb_class->odb_rvg_options['logging_on'] == "Y")
	} // odb_add_log()


	/********************************************************************************************
	 *	TRUNCATE THE LOG TABLE - v4.6
	 ********************************************************************************************/
	function odb_clear_log() {
		global $odb_class, $wpdb;

		$this->sql = "
		TRUNCATE TABLE `" . $odb_class->odb_logtable_name . "`
		";

		$wpdb->get_results($this->sql);
	} // clear_log()


	/********************************************************************************************
	 *	VIEW THE LOGS - v4.6.1
	 ********************************************************************************************/
	function odb_view_log() {
		global $odb_class, $wpdb;

		$nonce = wp_create_nonce( 'rvg-optimize-database' );

		$this->sql = "
		SELECT * FROM `" . $odb_class->odb_logtable_name . "` ORDER BY odb_id ASC
		";
		$this->res = $wpdb->get_results($this->sql, ARRAY_A);

		$odb_class->odb_displayer_obj->display_header();
?>
<div class="odb-title-bar">
  <h2><?php _e('Logs','rvg-optimize-database')?></h2>
</div>
<br>
<br>
<div class="odb-log-table">
<table width="97%" border="0" cellspacing="6" cellpadding="0">
<?php

		echo '
  <tr valign="top">
		';

		echo '<th align="left">'.__('date','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('deleted<br>revisions','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('deleted<br>trash','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('deleted<br>spam','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('deleted<br>tags','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('deleted<br>transients','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('deleted<br>pingbacks<br>trackbacks','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('deleted<br>oEmbed<br>records','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('deleted<br>orphans','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('nr of<br>optimized<br>tables','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('database<br> size<br>BEFORE','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('database<br>size<br>AFTER','rvg-optimize-database').'</th>';
		echo '<th align="right">'.__('SAVINGS','rvg-optimize-database').'</th>';
		echo '
  </tr>
		';

		for($i = 0; $i < count($this->res); $i++) {
			echo '
  <tr valign="top">
			';
			echo '<td>' . $this->res[$i]['odb_timestamp'] .'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_revisions'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_trash'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_spam'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_tags'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_transients'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_pingbacks'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_oembeds'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_orphans'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_tables'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_before'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_after'].'</td>';
			echo '<td align="right">' . $this->res[$i]['odb_savings'].'</td>';
			echo '
  </tr>
			';
		}

		echo '
</table>
		';
		// v4.6.2
		$msg = str_replace("'", "\'", __('Clear the log?', 'rvg-optimize-database'));
?>
<script>
function odb_confirm_delete() {
	if(confirm('<?php echo $msg?>')) {
		self.location = 'tools.php?page=rvg-optimize-database&action=clear_log&_wpnonce=<?php echo esc_attr( $nonce ); ?>'
		return;
	}
} // function odb_confirm_delete()
</script>
<br>
<input class="button odb-normal" type="button" name="clear_log" value="<?php _e('Clear Log', 'rvg-optimize-database') ?>" onclick="return odb_confirm_delete();" />
<br><br>
<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'odb_settings_page' ), admin_url( 'options-general.php' ) ) ); ?>"><?php _e( 'Change Settings', 'rvg-optimize-database' ); ?></a>
<?php
	} // odb_view_log()


	/********************************************************************************************
	 *	GET THE NUMBER OF LOG RECORDS - v4.6
	 ********************************************************************************************/
	function odb_log_count() {
		global $odb_class, $wpdb;

		$this->sql = "
		SELECT COUNT(*) AS logcnt FROM `" . $odb_class->odb_logtable_name . "`
		";
		$this->res = $wpdb->get_results($this->sql, ARRAY_A);
		return $this->res[0]['logcnt'];
	} // odb_log_count()

} // ODB_Logger



