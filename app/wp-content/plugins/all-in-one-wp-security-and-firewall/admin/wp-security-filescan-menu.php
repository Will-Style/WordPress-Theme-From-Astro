<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * AIOWPSecurity_Filescan_Menu class for scanning file changes, maleware and available updates.
 *
 * @access public
 */
class AIOWPSecurity_Filescan_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * File scan menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_FILESCAN_MENU_SLUG;

	/**
	 * Constructor adds menu for Scanner
	 */
	public function __construct() {
		parent::__construct(__('Scanner', 'all-in-one-wp-security-and-firewall'));
	}
	
	/**
	 * This function will setup the menus tabs by setting the array $menu_tabs
	 *
	 * @return void
	 */
	protected function setup_menu_tabs() {
		$menu_tabs = array(
			'file-change-detect' => array(
				'title' => __('File change detection', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_file_change_detect'),
			),
			'malware-scan' => array(
				'title' => __('Malware scan', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_malware_scan'),
			),
		);

		$this->menu_tabs = array_filter($menu_tabs, array($this, 'should_display_tab'));
	}
	
	/**
	 * File change detection on your system files.
	 *
	 * @global $wpdb
	 * @global $aio_wp_security
	 * @global $aiowps_feature_mgr
	 */
	protected function render_file_change_detect() {
		global $aio_wp_security;

		$scanner_data = AIOWPSecurity_Utility_File::get_scanner_data();

		$aio_wp_security->include_template('wp-admin/scanner/file-change-detect.php', false, $scanner_data);
	}
	
	/**
	 * Malware code scan on your system files.
	 *
	 * @return void
	 */
	protected function render_malware_scan() {
		global $aio_wp_security;
		
		$aio_wp_security->include_template('wp-admin/scanner/malware-scan.php', false, array());
	}
}
