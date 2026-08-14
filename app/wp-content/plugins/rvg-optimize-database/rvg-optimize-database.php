<?php
/*
Plugin Name: Optimize Database after Deleting Revisions
Description: One-click database optimization with precise revision cleanup and flexible scheduling.
Author: NerdPress
Author URI: https://www.nerdpress.net
Network: True
Version: 5.3.0
*/

/********************************************************************************************
 *
 *	MAIN CLASS
 *
 ********************************************************************************************/

add_action( 'plugins_loaded', function() {
	//v5.0.10
	$action = '';
	if(isset($_REQUEST['action']) && isset($_REQUEST['page']) && 'rvg-optimize-database' === $_REQUEST['page']) {
		$action = $_REQUEST['action'];
	}
	if (
		! empty( $action )
		&& isset( $_GET['_wpnonce'] )
		&& current_user_can( 'manage_options' )
		&& wp_verify_nonce( $_GET['_wpnonce'], 'rvg-optimize-database' )
	) {
		if ($action == 'analyze_summary' ||
			$action == 'analyze_detail' ||
			$action == 'run_summary' ||
			$action == 'run_detail') {
			@set_time_limit(4*3600);
		} else {
			// GET DEFAULT VALUE FROM PHP.INI
			$max = ini_get('max_execution_time');
			@set_time_limit($max);
		}
	} else {
		// Possibly invalid nonce; assume NO action.
		$action = '';
	}

	class OptimizeDatabase {
		// VERSION
		var $odb_version           = '5.3.0';

		// PLUGIN OPTIONS
		var $odb_rvg_options       = array();

		// EXCLUDED TABLES
		var $odb_rvg_excluded_tabs = array();

		// MULTISITE STRUCTURE
		var $odb_ms_prefixes       = array();

		// DATABASE TABLES
		var $odb_tables            = array();

		// MINIFYING?
		var $odb_minify;

		// MAIN PLUGIN FILE
		var $odb_main_file         = 'rvg-optimize-database/rvg-optimize-database.php';

		// LOCALIZATION
		var $odb_txt_domain        = 'rvg-optimize-database';

		// CURRENT SITE DATE (yyyymmddHHiiss) AND UNIX TIMESTAMP, BASED ON TIMEZONE OF THE SITE
		// v4.4.3
		var $odb_current_date;
		var $odb_timestamp;
		var $odb_last_run_seconds;

		// PLUGIN
		var $odb_plugin_url;
		var $odb_plugin_path;
		var $odb_logfile_debug_path;

		// DATABASE TABLE FOR LOGGING
		// v4.6
		var $odb_logtable_name;

		// OBJECTS
		var $odb_cleaner_obj;
		var $odb_displayer_obj;
		var $odb_logger_obj;
		var $odb_multisite_obj;
		var $odb_scheduler_obj;
		var $odb_utilities_obj;

		// PAGE TIMER
		var	$odb_start_time;

		var $log_arr;

		/*******************************************************************************
		 *
		 * 	CONSTRUCTOR
		 *
		 *******************************************************************************/
		function __construct() {
			// INITIALIZE PLUGIN
			add_action('init', array(&$this, 'odb_init'));
		} // __construct()


		/*******************************************************************************
		 * 	INITIALIZE PLUGIN
		 *******************************************************************************/
		function odb_init() {
			// LOAD CLASSES
			$this->odb_classes();

			// URLS AND DIRECTORIES
			$this->odb_urls_dirs();

			// 5.0.8 THIS MAY NOT THIS MAY NOT EVEN BE NECESSARY ANYMORE
			if ($this->odb_is_relevant_page()) {
				$this->odb_create_log_table();
			}

			// GET (MULTI-SITE) NETWORK INFORMATION
			$this->odb_multisite_obj->odb_ms_network_info();

			// LOAD OPTIONS
			$this->odb_load_options();

			// INITIALIZE WORDPRESS HOOKS
			$this->odb_init_hooks();

			// GET EXCLUDED TABLES FROM SETTINGS
			$this->odb_rvg_excluded_tabs = $this->odb_multisite_obj->odb_ms_get_option('odb_rvg_excluded_tabs');

			// USE THE NON-MINIFIED VERSION OF SCRIPTS AND STYLE SHEETS WHILE DEBUGGING
			$this->odb_minify = (defined('WP_DEBUG') && WP_DEBUG) ? '' : '.min';

			// LOAD STYLE SHEET (ONLY ON RELEVANT PAGES)
			// v4.0.3
			if($this->odb_is_relevant_page()) {
				wp_register_style(
					'odb-style'.$this->odb_version,
					plugins_url('css/style.css', __FILE__),
					array(),
					$this->odb_version
				);
				wp_enqueue_style('odb-style'.$this->odb_version);
			} // if($this->odb_is_relevant_page())

			if(defined('RUN_OPTIMIZE_DATABASE') && RUN_OPTIMIZE_DATABASE == 1) $this->odb_start(true);
		} // odb_init()


		/*******************************************************************************
		 *
		 * 	LOAD AND INITIALIZE CLASSES
		 *
		 *******************************************************************************/
		function odb_classes() {
			// LOAD CLASSES
			include_once('classes/odb-cleaner.php');
			include_once('classes/odb-displayer.php');
			include_once('classes/odb-logger.php');
			include_once('classes/odb-multisite.php');
			include_once('classes/odb-scheduler.php');
			include_once('classes/odb-utilities.php');

			// CREATE INSTANCES
			$this->odb_cleaner_obj   = new ODB_Cleaner();
			$this->odb_displayer_obj = new ODB_Displayer();
			$this->odb_logger_obj    = new ODB_Logger();
			$this->odb_multisite_obj = new ODB_MultiSite();
			$this->odb_scheduler_obj = new ODB_Scheduler();
			$this->odb_utilities_obj = new ODB_Utilities();
		} // odb_classes()


		/*******************************************************************************
		 *
		 * 	INITIALIZE URLS AND DIRECTORIES
		 *
		 *******************************************************************************/
		function odb_urls_dirs() {
			$this->odb_plugin_url         = plugins_url( '/', __FILE__ );
			$this->odb_plugin_path        = plugin_dir_path(__FILE__);
			$this->odb_logfile_debug_path = $this->odb_plugin_path.'logs/rvg-optimize-db-log.txt';
		} // odb_urls_dirs()


		/*******************************************************************************
		 *
		 * 	LOAD OPTIONS
		 *
		 *******************************************************************************/
		function odb_load_options() {
			// GET OPTIONS
			$this->odb_rvg_options = $this->odb_multisite_obj->odb_ms_get_option('odb_rvg_options', array());

			if(!isset($this->odb_rvg_options['version']))
				// THIS VERSION IS FROM BEFORE 4.0: CONVERT OPTIONS
				$this->odb_convert_options();

			if(!isset($this->odb_rvg_options['adminbar']))
				$this->odb_rvg_options['adminbar']         = 'N';
			if(!isset($this->odb_rvg_options['adminmenu']))
				$this->odb_rvg_options['adminmenu']        = 'N';
			if(!isset($this->odb_rvg_options['clear_pingbacks']))
				$this->odb_rvg_options['clear_pingbacks']  = 'N';
			if(!isset($this->odb_rvg_options['clear_oembed']))
				$this->odb_rvg_options['clear_oembed']  = 'N';
			if(!isset($this->odb_rvg_options['clear_orphans']))
				$this->odb_rvg_options['clear_orphans']  = 'N';
			if(!isset($this->odb_rvg_options['clear_spam']))
				$this->odb_rvg_options['clear_spam']       = 'N';
			if(!isset($this->odb_rvg_options['clear_tags']))
				$this->odb_rvg_options['clear_tags']       = 'N';
			if(!isset($this->odb_rvg_options['clear_transients']))
				$this->odb_rvg_options['clear_transients'] = 'N';
			if(!isset($this->odb_rvg_options['clear_trash']))
				$this->odb_rvg_options['clear_trash']      = 'N';
			if(!isset($this->odb_rvg_options['delete_older']))
				$this->odb_rvg_options['delete_older']     = 'N';
			if(!isset($this->odb_rvg_options['rvg_revisions']))
				$this->odb_rvg_options['rvg_revisions']     = 'N';

			if(!isset($this->odb_rvg_options['last_run']))
				$this->odb_rvg_options['last_run']         = '';
			// v4.5.1
			if(!isset($this->odb_rvg_options['last_run_seconds']))
				$this->odb_rvg_options['last_run_seconds'] = '';
			if(!isset($this->odb_rvg_options['logging_on']))
				$this->odb_rvg_options['logging_on']       = 'N';
			if(!isset($this->odb_rvg_options['nr_of_revisions']))
				$this->odb_rvg_options['nr_of_revisions']  = '';
			// v4.1
			if(!isset($this->odb_rvg_options['older_than']))
				$this->odb_rvg_options['older_than']       = '';
			if(!isset($this->odb_rvg_options['optimize_innodb']))
				$this->odb_rvg_options['optimize_innodb']  = 'N';
			if(!isset($this->odb_rvg_options['schedule_type']))
				$this->odb_rvg_options['schedule_type']    = '';
			if(!isset($this->odb_rvg_options['schedule_hour']))
				$this->odb_rvg_options['schedule_hour']    = '';
			if(!isset($this->odb_rvg_options['total_savings']))
				$this->odb_rvg_options['total_savings']    = (int)0;
			if(!isset($this->odb_rvg_options['version']))
				$this->odb_rvg_options['version']          = $this->odb_version;

			// CUSTOM POST TYPES (from v4.4)
			if(!isset($this->odb_rvg_options['post_types'])) {
				$this->odb_rvg_options['post_types'] = array();
				$relevant_pts = $this->odb_utilities_obj->odb_get_relevant_post_types();
				// (CUSTOM) POST TYPES ARE PER DEFAULT ENABLED
				foreach($relevant_pts as $posttype) {
					$this->odb_rvg_options['post_types'][$posttype] = "Y";
				} // foreach($relevant_pts as $posttype)

				if (isset($this->odb_rvg_options['rev_post_type'])) {
					// UPGRADE FROM A VERSION < 4.4
					if ($this->odb_rvg_options['rev_post_type'] == 'page') {
						// PAGES ONLY: DISABLE 'post'
						$this->odb_rvg_options['post_types']['post'] = "N";
					} else if ($this->odb_rvg_options['rev_post_type'] == 'post') {
						// POSTS ONLY: DISABLE 'page'
						$this->odb_rvg_options['post_types']['page'] = "N";
					}
					unset($this->odb_rvg_options['rev_post_type']);
				} // if (isset($this->odb_rvg_options['rev_post_type']))
			} // if(!isset($this->odb_rvg_options['post_types']))

			// UPDATE OPTIONS
			$this->odb_multisite_obj->odb_ms_update_option('odb_rvg_options', $this->odb_rvg_options);

			// UPDATE SCHEDULER (IF NEEDED)
			$this->odb_scheduler_obj->odb_update_scheduler();
		} // odb_load_options()


		/*******************************************************************************
		 *
		 * 	COPY AND DELETE OPTIONS FROM PREVIOUS VERSIONS (BEFORE 4.0)
		 *
		 *******************************************************************************/
		function odb_convert_options() {
			global $wpdb;

			// STOP OLD SCHEDULER
			wp_clear_scheduled_hook('rvg_optimize_database');

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_total_savings');
			if($setting) {
				$this->odb_rvg_options['total_savings'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_total_savings');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_odb_total_savings');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_pingbacks');
			if($setting) {
				$this->odb_rvg_options['clear_pingbacks'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_pingbacks');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_clear_pingbacks');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_oembed');
			if($setting) {
				$this->odb_rvg_options['rvg_clear_oembed'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_oembed');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_clear_oembed');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_orphans');
			if($setting) {
				$this->odb_rvg_options['rvg_clear_orphans'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_orphans');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_clear_orphans');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_spam');
			if($setting) {
				$this->odb_rvg_options['clear_spam'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_spam');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_clear_spam');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_tags');
			if($setting) {
				$this->odb_rvg_options['clear_tags'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_tags');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_clear_tags');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_transients');
			if($setting) {
				$this->odb_rvg_options['clear_transients'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_transients');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_clear_transients');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_trash');
			if($setting)
			{	$this->odb_rvg_options['clear_trash'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_clear_trash');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_clear_trash');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_adminbar');
			if($setting) {
				$this->odb_rvg_options['adminbar'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_adminbar');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_odb_adminbar');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_adminmenu');
			if($setting) {
				$this->odb_rvg_options['adminmenu'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_adminmenu');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_odb_adminmenu');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_logging_on');
			if($setting) {
				$this->odb_rvg_options['logging_on'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_logging_on');
				$this->odb_multisite_obj->odb_ms_delete_option('rvg_odb_logging_on');
			}

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_number');
			if($setting)
				$this->odb_rvg_options['nr_of_revisions'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_number');
			$this->odb_multisite_obj->odb_ms_delete_option('rvg_odb_number');

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_schedule');
			if($setting)
				$this->odb_rvg_options['schedule_type'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_schedule');
			$this->odb_multisite_obj->odb_ms_delete_option('rvg_odb_schedule');

			$setting = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_schedulehour');
			if($setting)
				$this->odb_rvg_options['schedule_hour'] = $this->odb_multisite_obj->odb_ms_get_option('rvg_odb_schedulehour');
			$this->odb_multisite_obj->odb_ms_delete_option('rvg_odb_schedulehour');

			// COPY EXCLUDED TABLES
			for($i = 0; $i < count($this->odb_ms_prefixes); $i++) {
				$sql = "
				SELECT `option_name`
					FROM ".$this->odb_ms_prefixes[$i]."options
				WHERE `option_name` LIKE 'rvg_ex_%'
				";

				$res = $wpdb->get_results($sql, ARRAY_A);
				for($j=0; $j<count($res); $j++) {
					$option_name = $res[$j]['option_name'];
					$option_name_new = substr($option_name, 7);
					$this->odb_rvg_excluded_tabs[$option_name_new] = 'Y';
					$this->odb_multisite_obj->odb_ms_delete_option($option_name);
				} // for($j=0; $j<count($res); $j++)
			} // for($i = 0; $i < count($this->odb_ms_prefixes); $i++)

			// UPDATE EXCLUDED TABLES
			$this->odb_multisite_obj->odb_ms_update_option('odb_rvg_excluded_tabs', $this->odb_rvg_excluded_tabs);
		} // odb_convert_options()


		/*******************************************************************************
		 *
		 * 	INITIALIZE WORDPRESS HOOKS
		 *
		 *******************************************************************************/
		function odb_init_hooks() {
			global $blog_id;

			// ADD ENTRY TO ADMIN TOOLS MENU
			if (is_multisite()) {
				if ($blog_id == 1) {
					// v4.1: PLUGIN ONLY CAN BE USED ON THE MAIN SITE (NOT ON THE SUB SITES)
					add_action('admin_menu', array(&$this, 'odb_admin_tools'));
					add_action('admin_menu', array(&$this, 'odb_admin_settings'));
					// ADD 'SETTINGS' LINK TO THE MAIN PLUGIN PAGE
					add_filter('plugin_action_links_'.plugin_basename(__FILE__), array(&$this, 'odb_settings_link'));
				} // if ($blog_id == 1)
			} else {
				add_action('admin_menu', array(&$this, 'odb_admin_tools'));
				add_action('admin_menu', array(&$this, 'odb_admin_settings'));
				// ADD 'SETTINGS' LINK TO THE MAIN PLUGIN PAGE
				add_filter('plugin_action_links_'.plugin_basename(__FILE__), array(&$this, 'odb_settings_link'));
			} // if (is_multisite())

			// ICON MODE: ADD ICON TO ADMIN MENU
			if (! empty( $this->odb_rvg_options['adminmenu'] ) && $this->odb_rvg_options['adminmenu'] == "Y") {
				add_action('admin_menu', array(&$this, 'odb_admin_icon'));
				add_action('admin_menu', array(&$this, 'odb_register_options'));
			}

			// ADD THE '1 CLICK OPTIMIZE DATABASE' ITEM TO THE ADMIN BAR (IF ACTIVATED)
			if(! empty( $this->odb_rvg_options['adminbar'] ) && $this->odb_rvg_options['adminbar'] == 'Y')
				add_action('wp_before_admin_bar_render', array(&$this, 'odb_admin_bar'));

			// INITIALIZE LOCALIZATION
			add_action('admin_menu', array(&$this, 'odb_i18n'));
		} // odb_init_hooks()


		/*******************************************************************************
		 *
		 * 	ADD ENTRY TO THE ADMIN TOOLS MENU
		 *
		 *******************************************************************************/
		function odb_admin_tools() {
			if (function_exists('add_management_page')) {
				add_management_page(
					__('Optimize Database',$this->odb_txt_domain),	// page title
					__('Optimize Database',$this->odb_txt_domain),	// menu title
					'manage_options',								// capability
					'rvg-optimize-database',						// menu slug
					array(&$this, 'odb_start_manually'));			// function
			} // if (function_exists('add_management_page'))
		} // odb_admin_tools()


		/*******************************************************************************
		 *
		 * 	ADD ENTRY TO THE ADMIN SETTINGS MENU
		 *
		 *******************************************************************************/
		function odb_admin_settings() {
			if (function_exists('add_options_page'))
				add_options_page(
					__('Optimize Database', $this->odb_txt_domain),	// page title
					__('Optimize Database', $this->odb_txt_domain),	// menu title
					'manage_options',								// capability
					'odb_settings_page',							// menu slug
					array(&$this, 'odb_settings_page')				// function
				);
		} // odb_admin_settings()


		/*******************************************************************************
		 *
		 * 	ADD 'SETTINGS' LINK TO THE MAIN PLUGIN PAGE
		 *
		 *******************************************************************************/
		function odb_settings_link($links) {
			array_unshift($links, '<a href="options-general.php?page=odb_settings_page">'.__('Settings', $this->odb_txt_domain).'</a>');
			return $links;
		} // odb_settings_link()


		/********************************************************************************************
		 *
		 *	ADD THE '1 CLICK OPTIMIZE DATABASE' ITEM TO THE ADMIN BAR (IF ACTIVATED)
		*
		********************************************************************************************/
		function odb_admin_bar() {
			global $wp_admin_bar;

			if (!is_super_admin() || !is_admin_bar_showing()) return;

			$siteurl = site_url('/');
			$nonce   = wp_create_nonce( 'rvg-optimize-database' );

			$wp_admin_bar->add_menu(
				array(
				'id'    => 'optimize',
				'title' => __('Optimize DB (1 click)', $this->odb_txt_domain),
				'href'  => $siteurl . 'wp-admin/tools.php?page=rvg-optimize-database&action=run_detail&_wpnonce=' . $nonce ));
		} // odb_admin_bar()


		/********************************************************************************************
		 *
		 *	'ICON MODE': ADD A LINK TO THE ADMIN MENU
		*
		********************************************************************************************/
		function odb_admin_icon() {
			if (function_exists('add_menu_page')) {
				add_menu_page(
					__('Optimize Database', $this->odb_txt_domain),			// page title
					__('Optimize Database', $this->odb_txt_domain), 		// menu title
					'administrator',										// capability
					'rvg-optimize-database',								// menu slug
					array(&$this, 'odb_start_manually'),					// function
					$this->odb_plugin_url.'images/icon.png'					// icon url
				);
			}
		} // odb_admin_icon()


		/********************************************************************************************
		 *
		 *	'ICON MODE': REGISTER OPTION PAGE BUT HIDE IT FROM THE ADMIN MENU
		*
		********************************************************************************************/
		function odb_register_options() {
			if (function_exists('add_submenu_page')) {
				add_submenu_page(
					'',											// parent slug (NULL is hide from menu)
					__('Optimize Database', $this->odb_txt_domain),	// page title
					__('Optimize Database', $this->odb_txt_domain),	// menu title
					'manage_options',								// capability
					'rvg_odb_admin',								// menu slug
					array(&$this, 'odb_settings_page')				// function
				);
			} // if (function_exists('add_submenu_page'))
		} // odb_register_options()


		/*******************************************************************************
		 *
		 * 	LOAD TEXT DOMAIN
		 *
		 *******************************************************************************/
		function odb_i18n() {
			load_plugin_textdomain(
				$this->odb_txt_domain,								// domain
				false,												// abs rel path
				dirname(plugin_basename( __FILE__ )).'/language/'	// plugin rel path
			);
		} // odb_i18n()


		/*******************************************************************************
		 *
		 * 	ARE WE ON A, FOR THIS PLUGIN, RELEVANT PAGE?
		 *	Since v4.0.3
		*
		*******************************************************************************/
		function odb_is_relevant_page() {
			$this_page = '';
			if(isset($_GET['page'])) {
				$this_page = $_GET['page'];
				return ($this_page == 'odb_settings_page' || $this_page == 'rvg-optimize-database');
			}
			return false;
		} // odb_is_relevant_page()


		/*******************************************************************************
		 *
		 * 	LOAD SETTINGS (OPTIONS) PAGE
		 *
		 *******************************************************************************/
		function odb_settings_page() {
			global $wpdb, $odb_rvg_excluded_tabs, $odb_ms_prefixes;

			include_once(trailingslashit(dirname(__FILE__)).'/includes/settings-page.php');
		} // odb_settings_page()


		/*******************************************************************************
		 *
		 * 	START CLEANING / OPTIMIZATION (INITIATED BY THE USER, NOT FROM WP-CRON)
		 *
		 *******************************************************************************/
		function odb_start_manually() {
			$this->odb_start(false);
		} // odb_start_manually()


		/*******************************************************************************
		 *
		 * 	START CLEANING / OPTIMIZATION (FROM WP-CRON)
		 *
		 *******************************************************************************/
		function odb_start_scheduler() {
			$this->odb_start(true);
		} // odb_start_scheduler()


		/*******************************************************************************
		 *
		 * CREATE THE LOG TABLE (IF NOT EXISTS)
		 *
		 *******************************************************************************/
		function odb_create_log_table() {
			global $wpdb;

			// PLUGIN RUNNING (v5.0.5)
			$this->odb_tables = $this->odb_utilities_obj->odb_get_tables();

			// CREATE LOG TABLE (IF NOT EXISTS) - v4.6
			$this->odb_logtable_name = $wpdb->base_prefix . 'odb_logs';

			$found = false;
			for($i = 0; $i < count($this->odb_tables); $i++) {
				if ($this->odb_tables[$i][0] == $this->odb_logtable_name) {
					$found = true;
				}
			} // for($i = 0; $i < count($this->odb_tables); $i++)

			// v5.0.3
			if (!$found) {
				$sql = '
					CREATE TABLE IF NOT EXISTS `' . $this->odb_logtable_name . '` (
					`odb_id`			int(11) NOT NULL AUTO_INCREMENT,
					`odb_timestamp`	varchar(20) NOT NULL,
					`odb_revisions`	int(11) NOT NULL,
					`odb_trash`		int(11) NOT NULL,
					`odb_spam`		int(11) NOT NULL,
					`odb_tags`		int(11) NOT NULL,
					`odb_transients`	int(11) NOT NULL,
					`odb_pingbacks`	int(11) NOT NULL,
					`odb_oembeds`		int(11) NOT NULL,
					`odb_orphans`		int(11) NOT NULL,
					`odb_tables`		int(11) NOT NULL,
					`odb_before`		varchar(20) NOT NULL,
					`odb_after`		varchar(20) NOT NULL,
					`odb_savings`		varchar(20) NOT NULL,
					PRIMARY KEY (`odb_id`)
					) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
					';

				// CREATE TABLE
				$wpdb->query($sql);
			} // if (!$found)
		} // odb_create_log_table()


		/*******************************************************************************
		 *
		 * 	MAIN METHOD FOR CLEANING / OPTIMIZING
		 *
		 *******************************************************************************/
		function odb_start($scheduler) {
			$this->odb_create_log_table();

			// Ensure current user (if applicable) can view this page.
			if(!$scheduler) {
				if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
					return;
				}
			}

			// PAGE LOAD TIMER
			$time  = microtime();
			$time  = explode(' ', $time);
			$time  = $time[1] + $time[0];
			$this->odb_start_time = $time;

			$action = '';
			if(isset($_REQUEST['action']) && isset($_REQUEST['page']) && 'rvg-optimize-database' === $_REQUEST['page']) {
				$action = $_REQUEST['action'];
			}
			if (
				! empty( $action )
				&& isset( $_GET['_wpnonce'] )
				&& wp_verify_nonce( $_GET['_wpnonce'], 'rvg-optimize-database' )
			) {
				// v4.6
				if($action == 'view_log') {
					// SHOW THE LOGS
					$this->odb_logger_obj->odb_view_log();
					// v4.6.1
					return;
				} else if($action == "clear_log") {
					// CLEAR THE LOG TABLE
					$this->odb_logger_obj->odb_clear_log();

					// UPDATED MESSAGE
					// v4.6
					echo "<script>jQuery('#odb-running').hide();</script>";
					echo "<div class='updated odb-bold'><p>".
						__('Optimize Database after Deleting Revisions LOGS HAVE BEEN CLEARED', $this->odb_txt_domain);
					echo "</p></div>";
				} // if($action == "clear_log")
			} // if(isset($_REQUEST['action']))
			else {
				// Assume there is no action or the nonce failed verification.
				$action = '';
			}

			if(!$scheduler) {
				// SHOW PAGE HEADER
				$this->odb_displayer_obj->display_header();
				// v4.1.9: STARTING: SHOW RUNNING INDICATOR
				echo "<script>jQuery('#odb-running').show();</script>";
				// SHOW CURRENT SETTINGS
				$this->odb_displayer_obj->display_current_settings();
			} // if(!$scheduler)

			if ($action != 'analyze_summary' &&
				$action != 'analyze_detail' &&
				$action != 'run_summary' &&
				$action != 'run_detail' &&
				!$scheduler) {
				/****************************************************************************************
				 *	START SCREEN (SHOW SETTINGS + BUTTONS)
				****************************************************************************************/
				$this->odb_displayer_obj->display_start_buttons($action);
			} else {
				$analyze = ($action == 'analyze_summary' || $action == 'analyze_detail');
				/****************************************************************************************
				 *	RUN CLEANING AND OPTIMIZATION
				****************************************************************************************/
				$this->odb_displayer_obj->display_start_buttons($action);

				// DELETE REDUNDANT DATA
				$this->odb_cleaner_obj->odb_run_cleaner($scheduler, $action);

				if ($scheduler || $action == 'run_summary' || $action == 'run_detail') {
					// REGISTER THE LAST RUN
					$this->odb_rvg_options['last_run'] = current_time('M j, Y @ H:i', 0);
					// OPTIMIZE DATABASE TABLES
					$this->odb_cleaner_obj->odb_run_optimizer($scheduler, $action);
					// SHOW RESULTS
					$this->odb_cleaner_obj->odb_savings($scheduler, $action);
				} // if (!$analyze)

				// SHOW 'DONE' PARAGRAPH
				if (!$scheduler && !$analyze)
					$this->odb_cleaner_obj->odb_done($analyze);

				if ($analyze && $this->odb_cleaner_obj->grand_total > 0) {
					$msg1 =  __("CLICK ONE OF THE 'OPTIMIZE' BUTTONS TO ACTUALLY DELETE THE FOUND ITEMS", $this->odb_txt_domain);
					$btn1 =  __('Cancel', $this->odb_txt_domain);
					$btn2 =  __('Optimize (summary)', $this->odb_txt_domain);
					$btn3 =  __('Optimize (detail)', $this->odb_txt_domain);
					?>
	<div class="odb-title-bar">
	<h2><?php _e('Analysis Done!', $this->odb_txt_domain)?></h2>
	</div>
	<br><br>
	<div id="odb-start-buttons" class="odb-padding-left">
	<p>
	<h4 class="odb-red odb-bold"><?php echo $msg1?></h4>
	<br>
	<?php $nonce = wp_create_nonce( 'rvg-optimize-database' ); ?>
	<input class="button odb-normal" type="button" name="cancel" value="<?php echo $btn1?>" onclick="self.location='tools.php?page=rvg-optimize-database'">
	&nbsp;
	&nbsp;<input class="button-primary button-large" type="button" name="optimize_summary" value="<?php echo $btn2?>" onclick="self.location='tools.php?page=rvg-optimize-database&action=run_summary&_wpnonce=<?php echo esc_attr( $nonce ); ?>'" class="odb-bold">
	&nbsp;
	&nbsp;<input class="button-primary button-large" type="button" name="optimize_detail" value="<?php echo $btn3?>" onclick="self.location='tools.php?page=rvg-optimize-database&action=run_detail&_wpnonce=<?php echo esc_attr( $nonce ); ?>'" class="odb-bold">

	</p>
	</div><!-- /odb-start-buttons -->
					<?php
				} // if ($analyze)

				$this->odb_rvg_options['last_run_seconds'] = $this->odb_last_run_seconds;

				$this->odb_multisite_obj->odb_ms_update_option('odb_rvg_options', $this->odb_rvg_options);
			}  // if ($action != 'analyze_summary' && ...

			if(!defined('RUN_OPTIMIZE_DATABASE')) {
				// v4.8.0: DONE: HIDE RUNNING INDICATOR
				echo "<script>jQuery('#odb-running').hide();</script>";
			} // if(!defined('RUN_OPTIMIZE_DATABASE'))
		} // odb_start()
	} // OptimizeDatabase

	// CREATE INSTANCE
	global $odb_class;
	$odb_class = new OptimizeDatabase();
} );

/*******************************************************************************
 *
 * 	PLUGIN DE-ACTIVATION
 *
 *******************************************************************************/
if ( ! function_exists( 'odb_deactivation_handler' ) ) {
	function odb_deactivation_handler() {
		// STOP SCHEDULER
		wp_clear_scheduled_hook('odb_scheduler');
	} // odb_deactivation_handler()
}

register_deactivation_hook(__FILE__, 'odb_deactivation_handler');

/*******************************************************************************
 *
 * 	PLUGIN UN-INSTALLATION
 *
 *******************************************************************************/
if ( ! function_exists( 'odb_uninstallation_handler' ) ) {
	function odb_uninstallation_handler() {
		// STOP SCHEDULER
		wp_clear_scheduled_hook('odb_scheduler');

		// DELETE THE OPTIONS
		delete_option('odb_rvg_options');
		delete_option('odb_rvg_excluded_tabs');

		delete_site_option('odb_rvg_options');
		delete_site_option('odb_rvg_excluded_tabs');

		// DROP THE LOG TABLE
		global $wpdb;
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}odb_logs");
	} // odb_uninstallation_handler()
}

register_uninstall_hook(__FILE__, 'odb_uninstallation_handler');
