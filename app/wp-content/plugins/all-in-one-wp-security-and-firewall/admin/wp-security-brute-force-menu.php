<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * AIOWPSecurity_Brute_Force_Menu class for brute force prevention.
 *
 * @access public
 */
class AIOWPSecurity_Brute_Force_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * Blacklist menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_BRUTE_FORCE_MENU_SLUG;

	/**
	 * Constructor adds menu for Brute force
	 */
	public function __construct() {
		parent::__construct(__('Brute force', 'all-in-one-wp-security-and-firewall'));
	}

	/**
	 * This function will setup the menus tabs by setting the array $menu_tabs
	 *
	 * @return void
	 */
	protected function setup_menu_tabs() {
		$menu_tabs = array(
			'rename-login' => array(
				'title' => __('Rename login page', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_rename_login'),
			),
			'cookie-based-brute-force-prevention' => array(
				'title' => __('Cookie based brute force prevention', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_cookie_based_brute_force_prevention'),
				'display_condition_callback' => 'is_main_site',
			),
			'captcha-settings' => array(
				'title' => __('CAPTCHA settings', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_captcha_settings'),
			),
			'login-whitelist' => array(
				'title' => __('Login whitelist', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_login_whitelist'),
			),
			'404-detection' => array(
				'title' => __('404 detection', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_404_detection'),
			),
			'honeypot' => array(
				'title' => __('Honeypot', 'all-in-one-wp-security-and-firewall'),
				'render_callback' => array($this, 'render_honeypot'),
			),
		);

		$this->menu_tabs = array_filter($menu_tabs, array($this, 'should_display_tab'));
	}

	/**
	 * Rename login page tab.
	 *
	 * @global $aio_wp_security
	 * @global $aiowps_feature_mgr
	 */
	protected function render_rename_login() {
		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();
		$rename_login_page_settings = $aios_commands->get_rename_login_page_data();

		$aio_wp_security->include_template('wp-admin/brute-force/rename-login.php', false, $rename_login_page_settings);
	}

	/**
	 * Cookie based brute force prevention tab.
	 *
	 * @global $aio_wp_security
	 * @global $aiowps_feature_mgr
	 *
	 * @return void
	 */
	protected function render_cookie_based_brute_force_prevention() {
		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();
		$cookie_based_brute_force_prevention_data = $aios_commands->get_cookie_based_brute_force_data();

		$aio_wp_security->include_template('wp-admin/brute-force/cookie-based-brute-force-prevention.php', false, $cookie_based_brute_force_prevention_data);
	}

	/**
	 * Login captcha tab.
	 *
	 * @global $aio_wp_security
	 * @global $aiowps_feature_mgr
	 *
	 * @return void
	 */
	protected function render_captcha_settings() {
		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();

		$captcha_settings_data = $aios_commands->get_captcha_settings_data();

		if ('cloudflare-turnstile' == $captcha_settings_data['aiowps_default_captcha'] && false === $captcha_settings_data['aios_google_recaptcha_invalid_configuration']) {
			echo '<div class="notice notice-warning aio_red_box"><p>' . esc_html__('Your Cloudflare Turnstile configuration is invalid.', 'all-in-one-wp-security-and-firewall').' ' . esc_html__('Please enter the correct Cloudflare Turnstile keys below to use the Turnstile feature.', 'all-in-one-wp-security-and-firewall').'</p></div>';
		}

		if ('1' == $captcha_settings_data['aios_google_recaptcha_invalid_configuration']) {
			echo '<div class="notice notice-warning aio_red_box"><p>' . esc_html__('Your Google reCAPTCHA configuration is invalid.', 'all-in-one-wp-security-and-firewall') . ' ' . esc_html__('Please enter the correct reCAPTCHA keys below to use the reCAPTCHA feature.', 'all-in-one-wp-security-and-firewall').'</p></div>';
		}

		$aio_wp_security->include_template('wp-admin/brute-force/captcha-settings.php', false, compact('captcha_settings_data'));
	}

	/**
	 * Login whitelist tab.
	 *
	 * @return void
	 * @global $aio_wp_security
	 * @global $aiowps_feature_mgr
	 */
	protected function render_login_whitelist() {
		global $aio_wp_security, $aiowps_feature_mgr;
		$ip_v4 = false;
		$your_ip_address = AIOWPSecurity_Utility_IP::get_user_ip_address();
		if (filter_var($your_ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) $ip_v4 = true;

		$aiowps_allowed_ip_addresses = $aio_wp_security->configs->get_value('aiowps_allowed_ip_addresses');
		$aio_wp_security->include_template('wp-admin/brute-force/login-whitelist.php', false, array('aiowps_feature_mgr' => $aiowps_feature_mgr, 'your_ip_address' => $your_ip_address, 'ip_v4' => $ip_v4, 'aiowps_allowed_ip_addresses' => $aiowps_allowed_ip_addresses));
	}

	/**
	 * Renders the 404 Detection tab
	 *
	 * @return void
	 */
	protected function render_404_detection() {
		global $aio_wp_security;

		include_once 'wp-security-list-404.php'; // For rendering the AIOWPSecurity_List_Table in basic-firewall tab
		$event_list_404 = new AIOWPSecurity_List_404(); // For rendering the AIOWPSecurity_List_Table in basic-firewall tab

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- PCP warning. No nonce available.
		$page = isset($_REQUEST['page']) ? sanitize_text_field(wp_unslash($_REQUEST['page'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- PCP warning. No nonce available.
		$tab = isset($_REQUEST["tab"]) ? sanitize_text_field(wp_unslash($_REQUEST["tab"])) : '';
		$aio_wp_security->include_template('wp-admin/brute-force/404-detection.php', false, array('event_list_404' => $event_list_404, 'page' => $page, 'tab' => $tab));
	}

	/**
	 * Honeypot tab.
	 *
	 * @global $aio_wp_security
	 * @global $aiowps_feature_mgr
	 *
	 * @return void
	 */
	protected function render_honeypot() {
		global $aio_wp_security;

		$aios_commands = new AIOWPSecurity_Commands();
		$honeypot_data = $aios_commands->get_honeypot_data();

		$aio_wp_security->include_template('wp-admin/brute-force/honeypot.php', false, $honeypot_data);
	}
}
