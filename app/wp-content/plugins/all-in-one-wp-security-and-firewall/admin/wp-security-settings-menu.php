<?php

if (!defined('ABSPATH')) die('No direct access.');

/**
 * AIOWPSecurity_Settings_Menu class for setting configs.
 *
 * @access public
 */
class AIOWPSecurity_Settings_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * Settings menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_SETTINGS_MENU_SLUG;

	/**
	 * Constructor adds menu for Settings
	 */
	public function __construct() {
		parent::__construct(__('Settings', 'all-in-one-wp-security-and-firewall'));
	}

	/**
	 * This function will setup the menus tabs by setting the array $menu_tabs
	 *
	 * @return void
	 */
	public function setup_menu_tabs() {
		$menu_tabs = array(
			'general-settings' => array(
				'title' => __('General settings', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_general_settings'),
			),
			'htaccess-file-operations' => array(
				'title' => '.htaccess '.__('file', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_htaccess_file_operations'),
				'display_condition_callback' => array('AIOWPSecurity_Utility_Permissions', 'is_main_site_and_super_admin'),
			),
			'wp-config-file-operations' => array(
				'title' => 'wp-config.php '.__('file', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_wp_config_file_operations'),
				'display_condition_callback' => array('AIOWPSecurity_Utility_Permissions', 'is_main_site_and_super_admin'),
			),
			'delete-plugin-settings' => array(
				'title' => __('Delete plugin settings', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_delete_plugin_settings_tab')
			),
			'wp-version-info' => array(
				'title' => __('WP version info', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_wp_version_info'),
			),
			'settings-file-operations' => array(
				'title' => __('Import/Export', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_settings_file_operations'),
			),
			'advanced-settings' => array(
				'title' => __('Advanced settings', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_advanced_settings'),
				'display_condition_callback' => array('AIOWPSecurity_Utility_Permissions', 'is_main_site_and_super_admin'),
			),
		);

		$menu_tabs = apply_filters('aiowpsecurity_setting_tabs', $menu_tabs);
		$this->menu_tabs = array_filter($menu_tabs, array($this, 'should_display_tab'));
	}

	/**
	 * Renders the submenu's general settings tab.
	 *
	 * @return void
	 */
	protected function render_general_settings() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/settings/general-settings.php', false, array());
	}

	/**
	 * Renders the submenu's htaccess file operations tab.
	 *
	 * @return void
	 */
	protected function render_htaccess_file_operations() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/settings/htaccess-file-operations.php', false, array());
	}

	/**
	 * Renders the submenu's wp config file operations tab.
	 *
	 * @return void
	 */
	protected function render_wp_config_file_operations() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/settings/wp-config-file-operations.php', false, array());
	}

	/**
	 * Renders the submenu's delete plugin settings tab.
	 *
	 * @return void
	 */
	protected function render_delete_plugin_settings_tab() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/settings/delete-plugin-settings.php', false, array());
	}

	/**
	 * Renders the submenu's wp version info tab.
	 *
	 * @return void
	 */
	protected function render_wp_version_info() {
		global $aio_wp_security, $aiowps_feature_mgr;

		$aio_wp_security->include_template('wp-admin/settings/wp-version-info.php', false, array('aiowps_feature_mgr' => $aiowps_feature_mgr));
	}

	/**
	 * Renders the submenu's settings file operations tab.
	 *
	 * @return void
	 */
	protected function render_settings_file_operations() {
		global $aio_wp_security;

		$events_table_name = AIOWPSEC_TBL_EVENTS;
		AIOWPSecurity_Utility::cleanup_table($events_table_name, 500);

		$aio_wp_security->include_template('wp-admin/settings/settings-file-operations.php', false, array());
	}

	/**
	 * Renders advanced settings tab.
	 *
	 * @return void
	 */
	protected function render_advanced_settings() {
		if (!is_main_site()) {
			return;
		}

		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();

		$advanced_settings_data = $aios_commands->get_ip_address_detection_data();

		$aio_wp_security->include_template('wp-admin/settings/advanced-settings.php', false, $advanced_settings_data);
	}
}
