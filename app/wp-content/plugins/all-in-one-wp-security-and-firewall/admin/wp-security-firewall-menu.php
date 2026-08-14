<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

use AIOWPS\Firewall\Allow_List;

class AIOWPSecurity_Firewall_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * Firewall menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_FIREWALL_MENU_SLUG;

	/**
	 * Constructor adds menu for Firewall
	 */
	public function __construct() {
		parent::__construct(__('Firewall', 'all-in-one-wp-security-and-firewall'));
	}

	/**
	 * This function will setup the menus tabs by setting the array $menu_tabs
	 *
	 * @return void
	 */
	protected function setup_menu_tabs() {
		$menu_tabs = array(
			'php-rules' => array(
				'title' => __('PHP rules', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_php_rules'),
			),
			'htaccess-rules' => array(
				'title' => __('.htaccess rules', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_htaccess_rules'),
				'display_condition_callback' => array('AIOWPSecurity_Utility', 'allow_to_write_to_htaccess'),
			),
			'6g-firewall' => array(
				'title' => __('6G firewall rules', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_6g_firewall'),
				'display_condition_callback' => array('AIOWPSecurity_Utility_Permissions', 'is_main_site_and_super_admin'),
			),
			'5g-firewall' => array(
				'title' => __('5G legacy rules', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_5g_firewall'),
				'display_condition_callback' => array('AIOWPSecurity_Utility', 'render_5g_legacy_tab'),
			),
			'internet-bots' => array(
				'title' => __('Internet bots', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_internet_bots'),
				'display_condition_callback' => array('AIOWPSecurity_Utility_Permissions', 'is_main_site_and_super_admin'),
			),
			'block-and-allow-lists' => array(
				'title' => __('Block & allow lists', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_block_and_allow_lists'),
				'display_condition_callback' => array('AIOWPSecurity_Utility_Permissions', 'is_main_site_and_super_admin'),
			),
			'advanced-settings' => array(
				'title' => __('Advanced settings', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_advanced_settings'),
				'display_condition_callback' => array('AIOWPSecurity_Utility_Permissions', 'is_main_site_and_super_admin'),
			)
		);

		$this->menu_tabs = array_filter($menu_tabs, array($this, 'should_display_tab'));
	}

	/**
	 * Renders the PHP Firewall settings tab
	 *
	 * @return void
	 */
	protected function render_php_rules() {
		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();

		$php_firewall_data = $aios_commands->get_php_firewall_data();

		$aio_wp_security->include_template('wp-admin/firewall/php-firewall-rules.php', false, compact('php_firewall_data'));
	}

	/**
	 * Renders the Htaccess Firewall tab
	 *
	 * @return void
	 */
	protected function render_htaccess_rules() {
		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();

		$htaccess_rules_data = $aios_commands->get_htaccess_rules_data();

		$aio_wp_security->include_template('wp-admin/firewall/htaccess-firewall-rules.php', false, compact('htaccess_rules_data'));
	}
	
	/**
	 * Renders the 6G Blacklist Firewall Rules tab
	 *
	 * @return void
	 */
	protected function render_6g_firewall() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/general/moved.php', false, array('key' => '6g'));
	}

	/**
	 * Renders the 5G Blacklist Firewall Rules tab
	 *
	 * @return void
	 */
	protected function render_5g_firewall() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/firewall/5g.php');
	}

	/**
	 * Renders the Internet Bots tab
	 *
	 * @return void
	 */
	protected function render_internet_bots() {
		global $aio_wp_security;

		$aio_wp_security->include_template('wp-admin/general/moved.php', false, array('key' => 'internet-bots'));
	}


	/**
	 * Renders the Advanced settings tab.
	 *
	 * @return void
	 */
	protected function render_advanced_settings() {
		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();

		$advanced_settings_data = $aios_commands->get_firewall_advanced_settings_data();

		$aio_wp_security->include_template('wp-admin/firewall/advanced-settings.php', false, compact('advanced_settings_data'));
	}

	/**
	 * Renders ban user tab for blacklist IPs and user agents
	 *
	 * @global $aio_wp_security
	 * @global $aiowps_feature_mgr
	 *
	 * @return void
	 */
	protected function render_block_and_allow_lists() {
		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();

		$block_allowlist_data = $aios_commands->get_block_allow_lists_data();

		$aio_wp_security->include_template('wp-admin/firewall/block-and-allow-lists.php', false, $block_allowlist_data);
	}

	/**
	 * Validates posted user agent list and set, save as config.
	 *
	 * @global $aio_wp_security
	 * @global $aiowps_firewall_config
	 *
	 * @param string $banned_user_agents
	 *
	 * @return int
	 */
	private function validate_user_agent_list($banned_user_agents) {
		global $aio_wp_security;
		$aiowps_firewall_config = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::CONFIG);
		$submitted_agents = AIOWPSecurity_Utility::splitby_newline_trim_filter_empty($banned_user_agents);
		$agents = array_unique(array_filter(array_map('sanitize_text_field', $submitted_agents), 'strlen'));
		$aio_wp_security->configs->set_value('aiowps_banned_user_agents', implode("\n", $agents));
		$aiowps_firewall_config->set_value('aiowps_blacklist_user_agents', $agents);
		$_POST['aiowps_banned_user_agents'] = ''; // Clear the post variable for the banned address list
		return 1;
	}
}
