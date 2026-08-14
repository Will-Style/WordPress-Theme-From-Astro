<?php
// phpcs:disable Squiz.ControlStructures.InlineIfDeclaration.NotSingleLine, PHPCompatibility.Syntax.NewArrayUnpacking.Found -- This code is only run for php >= 7.4.
if (!defined('ABSPATH')) die('No direct access allowed');

use Updraftplus\All_In_One_Wp_Security_And_Firewall\Wizard\Onboarding\Onboarding;

/**
 * AIOWPSecurity_Onboarding class for configuring the Onboarding Wizard.
 */
class AIOWPSecurity_Onboarding {

	private $is_premium = false;

	/**
	 * The Onboarding wizard library stores an internal prefix for the plugin that it uses for hooks.
	 *
	 * @var string
	 */
	const PREFIX = 'aios';

	/**
	 * When another plugin is installed through the Onboarding wizard, this is used to set the installation source.
	 *
	 * @var string
	 */
	const SLUG = 'all-in-one-wp-security-and-firewall';

	/**
	 * Details for the AIOS FluentCRM mailing list.
	 */
	const MAILING_LIST_FREE_ID    = 130;
	const MAILING_LIST_PREMIUM_ID = 131;
	const MAILING_LIST_ENDPOINT   = 'https://teamupdraft.com/?fluentcrm=1&route=contact&hash=69902751-58c5-460b-bd9f-456d62033c2b';

	/**
	 * Constructor for the class.
	 */
	public function __construct() {
		$this->is_premium = AIOWPSecurity_Utility_Permissions::is_premium_installed();

		add_action('rest_api_init', array($this, 'register_rest_routes'));
		add_filter(self::PREFIX.'_onboarding_steps', array($this, 'steps'));
		add_action(self::PREFIX.'_onboarding_update_options', array($this, 'update_step_settings'), 10, 2);

		$onboarding = new Onboarding();

		if ($onboarding::is_onboarding_active(self::PREFIX, self::SLUG)) {
			$onboarding->is_pro                         = $this->is_premium;
			$onboarding->prefix                         = self::PREFIX;
			$onboarding->mailing_list                   = array($this->is_premium ? self::MAILING_LIST_PREMIUM_ID : self::MAILING_LIST_FREE_ID);
			$onboarding->mailing_list_endpoint          = self::MAILING_LIST_ENDPOINT;
			$onboarding->caller_slug                    = self::SLUG;
			$onboarding->capability                     = $this->required_capability();
			$onboarding->plugin_name                    = $this->is_premium ? 'All-In-One Security Premium' : 'All-In-One Security';
			$onboarding->privacy_url_label              = __('Privacy Policy.', 'all-in-one-wp-security-and-firewall');
			$onboarding->privacy_statement_url          = $this->add_utm_params('https://teamupdraft.com/privacy/', 'privacy-statement');
			$onboarding->forgot_password_url            = $this->add_utm_params('https://teamupdraft.com/my-account/lost-password/', 'forgot-password');
			$onboarding->documentation_url              = $this->add_utm_params('https://teamupdraft.com/documentation/all-in-one-security/', 'documentation');
			$onboarding->upgrade_url                    = $this->add_utm_params('https://teamupdraft.com/all-in-one-security/pricing/', 'upgrade-to-premium', 'button');
			$onboarding->support_url                    = $this->is_premium ? $this->add_utm_params('https://teamupdraft.com/support/premium-support/', 'premium-support') : 'https://wordpress.org/support/plugin/all-in-one-wp-security-and-firewall/';
			$onboarding->page_prefix                    = AIOWPSEC_MAIN_MENU_SLUG;
			$onboarding->version                        = AIO_WP_SECURITY_VERSION;
			$onboarding->languages_dir                  = AIO_WP_SECURITY_PATH.'/languages';
			$onboarding->text_domain                    = $this->is_premium ? 'all-in-one-wp-security-and-firewall-premium' : 'all-in-one-wp-security-and-firewall';
			$onboarding->reload_settings_page_on_finish = true;
			$onboarding->logo_path                      = trailingslashit(AIO_WP_SECURITY_URL) . 'images/plugin-logos/aios-icon.png';
			$onboarding->exit_wizard_text               = __('Exit setup', 'all-in-one-wp-security-and-firewall');
			$onboarding->udmupdater_muid = 2;
			$onboarding->udmupdater_slug = 'all-in-one-wp-security-and-firewall-premium';
			$onboarding->init();
		}
	}

	/**
	 * Activates the Onboarding Wizard.
	 *
	 * @return void
	 */
	public static function activate() {
		set_transient(self::PREFIX . '_redirect_to_dashboard_page', true, 5 * MINUTE_IN_SECONDS);
		update_site_option(self::PREFIX . '_start_onboarding', true);
	}

	/**
	 * After activation, redirect the user to the AIOS dashboard page.
	 *
	 * @return void
	 */
	public static function maybe_redirect_to_dashboard_page() {
		if (get_option('teamupdraft_installation_source_all-in-one-wp-security-and-firewall') || get_site_option('teamupdraft_installation_source_all-in-one-wp-security-and-firewall')) {
			return;
		}
		if (get_transient(self::PREFIX . '_redirect_to_dashboard_page') && (!isset($_GET['page']) || AIOWPSEC_MAIN_MENU_SLUG !== $_GET['page'])) {
			delete_transient(self::PREFIX . '_redirect_to_dashboard_page');
			AIOWPSecurity_Utility::redirect_to_url(get_admin_url(get_main_site_id(), 'admin.php?page='.AIOWPSEC_MAIN_MENU_SLUG));
			exit;
		}
	}

	/**
	 * Add UTM parameters to a URL and return the modified URL.
	 *
	 * @param string $url             The original URL to be modified.
	 * @param string $content         UTM content parameter.
	 * @param string $creative_format UTM creative_format parameter.
	 *
	 * @return string
	 */
	private function add_utm_params($url, $content = 'onboarding', $creative_format = 'text') {
		$type = $this->is_premium ? 'prem' : 'free';

		$utm_params = array(
			'utm_source'  => 'aios',
			'utm_medium'  => 'referral',
			'utm_content'  => $content,
			'utm_campaign' => sprintf('paac-%s-onboarding-wizard', $type),
			'utm_creative_format'  => $creative_format,
		);

		return esc_url(add_query_arg($utm_params, $url));
	}

	/**
	 * Gets the user capability required by the Onboarding wizard.
	 *
	 * @return string
	 */
	private function required_capability() {
		return apply_filters('aios_management_permission', is_multisite() ? 'manage_network_options' : 'manage_options');
	}

	/**
	 * Check if the plugin license is connected via the Updraft updater instance.
	 *
	 * @return bool True if the license is connected, false otherwise.
	 */
	private function is_license_connected() {
		global $updraft_updater_instance;

		if (!isset($updraft_updater_instance)) {
			return true; // Don't show the license step if we fail to determine if the license is connected.
		}

		$option = $updraft_updater_instance->get_option($updraft_updater_instance->slug.'_updater_options');

		return empty($option['email']) ? false : true;
	}

	/**
	 * Filters onboarding steps.
	 *
	 * @global AIO_WP_Security_Simba_Two_Factor_Authentication_Plugin $simba_two_factor_authentication
	 *
	 * @return array
	 */
	public function steps() {
		global $simba_two_factor_authentication;

		$license_step = array();

		if ($this->is_premium && !$this->is_license_connected()) {
			$license_step[] = array(
				'id'       => 'license',
				'type'     => 'license',
				'icon'     => 'license',
				'title'    => __('Connect and activate your license', 'all-in-one-wp-security-and-firewall-premium'),
				'title_conditional' => array(
					'licenseActivated' => __('License activated', 'all-in-one-wp-security-and-firewall-premium'),
					'isUpdating' => __('Activating your Premium license...', 'all-in-one-wp-security-and-firewall-premium')
				),
				'subtitle' => __('Please enter your TeamUpdraft credentials to start using Premium features.', 'all-in-one-wp-security-and-firewall-premium'),
				'subtitle_conditional' => array(
					'licenseActivated' => '',
					'isUpdating' => ''
				),
				'fields'   => array(
					array(
						'id'    => 'registration_email',
						'type'  => 'email',
						'label' => __('Email', 'all-in-one-wp-security-and-firewall-premium'),
					),
					array(
						'id'    => 'registration_password',
						'type'  => 'password',
						'label' => __('Password', 'all-in-one-wp-security-and-firewall-premium'),
					),
				),
				'button'   => array(
					'id'    => 'activate',
					'label' => __('Activate', 'all-in-one-wp-security-and-firewall-premium'),
				),
			);
		}

		$php_firewall_required_extensions = array('filter', 'tokenizer');
		$lock_preload_firewall_rules = false;
		foreach ($php_firewall_required_extensions as $required_extension) {
			if (!extension_loaded($required_extension)) {
				$lock_preload_firewall_rules = true;
				break;
			}
		}

		$user_id = wp_get_current_user()->ID;
		$tfa_step = array();

		if (isset($simba_two_factor_authentication) && isset($simba_two_factor_authentication->get_controllers()['totp']) && !$simba_two_factor_authentication->is_activated_by_user($user_id)) {
			$totp_controller = $simba_two_factor_authentication->get_controller('totp');

			$algorithm_type = $totp_controller->get_user_otp_algorithm($user_id);

			if ('totp' != $algorithm_type) {
				$totp_controller->changeUserAlgorithmTo($user_id, 'totp');
			}

			$url = preg_replace('/^https?:\/\//i', '', site_url());

			$tfa_priv_key_64 = get_user_meta($user_id, 'tfa_priv_key_64', true);
			if (!$tfa_priv_key_64) $tfa_priv_key_64 = $totp_controller->addPrivateKey($user_id);
			$tfa_priv_key = trim($totp_controller->getPrivateKeyPlain($tfa_priv_key_64, $user_id), "\x00..\x1F");

			$qr_code_url = $totp_controller->tfa_qr_code_url('totp', $url, $tfa_priv_key, $user_id);
			$tfa_priv_key_32 = Base32::encode($tfa_priv_key);

			$tfa_step[] = array(
				'id'       => 'two_fa_qr_code',
				'type'     => 'settings',
				'icon'     => 'user-shield',
				'title'    => __('Configure your authenticator', 'all-in-one-wp-security-and-firewall'),
				'subtitle' => __('Add an extra layer of login security with a time-based code.', 'all-in-one-wp-security-and-firewall'),
				'groups'   => array(
					array(
						'title' => __('Configure your authenticator', 'all-in-one-wp-security-and-firewall'),
						'id'    => 'qr_code',
					),
					...($this->is_premium ? array(
						array(
							'title' => __('Save emergency codes', 'all-in-one-wp-security-and-firewall'),
							'id'    => 'two_fa_backup_codes',
						),
					) : array()),
					array(
						'title' => __('Verify setup', 'all-in-one-wp-security-and-firewall'),
						'id'    => 'verify_two_fa',
					),
				),
				'fields'   => array(
					array(
						'id'          => 'qr_code',
						'group_id'    => 'qr_code',
						'type'        => 'qr_code',
						'label'       => __('Scan the QR code with Google Authenticator (or similar), or enter this key into your app:', 'all-in-one-wp-security-and-firewall'),
						'value'       => $qr_code_url,
						'private_key' => $tfa_priv_key_32
					),
					...($this->is_premium ? array(
						array(
							'id'       => 'two_fa_backup_codes',
							'group_id' => 'two_fa_backup_codes',
							'type'     => 'backup_codes',
							'label'    => __('Store these backup codes securely.', 'all-in-one-wp-security-and-firewall') . ' ' . __('Use a code if you lose your authenticator - each code is valid only once.', 'all-in-one-wp-security-and-firewall'),
							'value'    => $totp_controller->get_emergency_codes_as_string($user_id, true)
						),
					) : array()),
					array(
						'id'          => 'two_fa_verification_code',
						'group_id'    => 'verify_two_fa',
						'type'        => 'two_fa_validation',
						'label'       => __('Enter the code generated by your app', 'all-in-one-wp-security-and-firewall'),
						'placeholder' => __('Enter the 6-digit code', 'all-in-one-wp-security-and-firewall'),
						'default'     => array(
							'key' => '',
							'validated' => false
						),
					),
				),
				'button'   => array(
					'id'    => 'save',
					'label' => __('Save and continue', 'all-in-one-wp-security-and-firewall'),
					'icon'  => 'continue-arrow-right'
				),
			);
		}

		$last_step_bullets = array(
			array(
				__('Malware scanning', 'all-in-one-wp-security-and-firewall'),
				__('Country blocking', 'all-in-one-wp-security-and-firewall'),
			),
			array(
				__('Sensitive file protection', 'all-in-one-wp-security-and-firewall'),
				__('Advanced 2FA', 'all-in-one-wp-security-and-firewall'),
			),
			array(
				__('Smart 404 configuration', 'all-in-one-wp-security-and-firewall'),
				__('Premium support & more', 'all-in-one-wp-security-and-firewall'),
			),
		);

		$steps = array(
			array(
				'id'            => 'intro',
				'type'          => 'intro',
				'title'         => __('Let\'s get started', 'all-in-one-wp-security-and-firewall'),
				'subtitle'      => __('Secure and protect your WordPress site with ease - trusted by over 1 million sites.', 'all-in-one-wp-security-and-firewall'),
				'intro_bullets' => array(
					array(
						'icon'  => 'key',
						'title' => __('Secure Login', 'all-in-one-wp-security-and-firewall'),
						'desc'  => __('Limit login attempts and lock out suspicious IPs.', 'all-in-one-wp-security-and-firewall'),
					),
					array(
						'icon'  => 'firewall',
						'title' => __('Firewall Protection', 'all-in-one-wp-security-and-firewall'),
						'desc'  => __('Block malicious requests before they reach WordPress.', 'all-in-one-wp-security-and-firewall'),
					),
					array(
						'icon'  => 'security',
						'title' => __('File & Database Protection', 'all-in-one-wp-security-and-firewall'),
						'desc'  => __('Audit & fix file permissions; secure database backups.', 'all-in-one-wp-security-and-firewall'),
					),
					array(
						'icon'  => 'user-lock',
						'title' => __('Two-Factor Authentication', 'all-in-one-wp-security-and-firewall'),
						'desc'  => __('Add an extra verification step.', 'all-in-one-wp-security-and-firewall'),
					),
				),
				'button'        => array(
					'id'    => 'start',
					'label' => __('Start', 'all-in-one-wp-security-and-firewall'),
					'icon'  => 'magic-wand'
				),
				'note'          => $this->is_premium ? __('Premium plugin   •   Quick setup   •   No tech skills needed', 'all-in-one-wp-security-and-firewall') : __('Free plugin   •   Quick setup   •   No tech skills needed', 'all-in-one-wp-security-and-firewall'),
			),
			...$license_step,
			array(
				'id'       => 'settings',
				'type'     => 'settings',
				'icon'     => 'settings',
				'title'    => __('Enable best-practice settings', 'all-in-one-wp-security-and-firewall'),
				'subtitle' => __('We\'ve pre-selected core settings to secure and protect your site.', 'all-in-one-wp-security-and-firewall') . ' ' . __('You can tweak them anytime.', 'all-in-one-wp-security-and-firewall'),
				'groups'   => array(
					array(
						'title' => __('User Security', 'all-in-one-wp-security-and-firewall'),
						'id'    => 'user_security',
					),
					array(
						'title' => __('File Security', 'all-in-one-wp-security-and-firewall'),
						'id'    => 'file_security',
					),
					array(
						'title' => __('Spam Prevention', 'all-in-one-wp-security-and-firewall'),
						'id'    => 'spam_prevention',
					),
					array(
						'title' => __('Firewall', 'all-in-one-wp-security-and-firewall'),
						'id'    => 'firewall',
					),
				),
				'fields'   => array(
					array(
						'id'       => 'prevent_user_enumeration',
						'group_id' => 'user_security',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Stops attackers from discovering your site\'s usernames by blocking common techniques used to scan for valid user accounts.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Prevent user enumeration', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'login_lockdown',
						'group_id' => 'user_security',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Temporarily blocks IP addresses after multiple failed login attempts to prevent brute force attacks on your admin area.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Login lockdown (recommended limits)', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'force_logout',
						'group_id' => 'user_security',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Automatically logs out inactive users after a set time period to prevent unauthorized access from unattended sessions.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Force logout (recommended 60 min)', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'         => 'tfa_all_roles',
						'group_id'   => 'user_security',
						'type'       => 'checkbox',
						'subtype'    => 'switch',
						'tooltip'    => array(
							'heading' => $this->is_premium ? '' : __('Premium feature ⚡', 'all-in-one-wp-security-and-firewall'),
							// translators: %s: 'Upgrade to Premium' link.
							'text'    => $this->is_premium ? __('Make everyone enter a code from an authenticator app on their phone.', 'all-in-one-wp-security-and-firewall') . ' <strong>' . __('Warning: if your site has pre-existing users this feature can lock them out of your site and require manual intervention to let them back in.', 'all-in-one-wp-security-and-firewall') . '</strong>' : sprintf(__('%s to unlock this and other advanced options.', 'all-in-one-wp-security-and-firewall'), '<a href=' . $this->add_utm_params('https://teamupdraft.com/all-in-one-security/pricing/', 'upgrade-to-premium', 'tooltip') . ' class="font-bold hover:text-orange-dark underline" target="_blank">' . __('Upgrade to Premium', 'all-in-one-wp-security-and-firewall') . '</a>')
						),
						'is_lock'    => !$this->is_premium,
						'label'      => __('Require two-factor authentication for all roles', 'all-in-one-wp-security-and-firewall'),
						'default'    => false,
					),
					array(
						'id'       => 'disable_php_file_editing',
						'group_id' => 'file_security',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Removes the ability to edit PHP files directly from the WordPress admin, preventing malicious code injection if your admin is compromised.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Disable PHP file editing', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'enable_iframe_protection',
						'group_id' => 'file_security',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Prevents your website from being embedded in malicious iframes on other sites, protecting against clickjacking attacks.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Enable iFrame protection', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'copy_protection',
						'group_id' => 'file_security',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Adds basic protection against content theft by disabling right-click, text selection, and common keyboard shortcuts for copying.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Copy protection', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'detect_spambots',
						'group_id' => 'spam_prevention',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Identifies and flags suspected spam comments for review.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Detect spambots (mark, don\'t discard)', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'auto_block_ip_after_3_spam_comments',
						'group_id' => 'spam_prevention',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Automatically bans IP addresses that submit multiple spam comments, preventing repeat offenders from continuing attacks.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Auto block IP after 3 spam comments', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'block_xmlrpc',
						'group_id' => 'firewall',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Disables WordPress\'s XML-RPC interface, which is often exploited for brute force attacks and DDoS amplification.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Block XMLRPC', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'turn_on_6g_method_blocking',
						'group_id' => 'firewall',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							'text' => __('Activates advanced firewall rules that block known malicious request patterns and common attack vectors.', 'all-in-one-wp-security-and-firewall')
						),
						'label'    => __('Turn on 6G method blocking', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					array(
						'id'       => 'preload_firewall_rules',
						'group_id' => 'firewall',
						'type'     => 'checkbox',
						'subtype'  => 'switch',
						'tooltip'  => array(
							// translators: %s: Comma-separated list of PHP extensions.
							'heading' => $lock_preload_firewall_rules ? sprintf(__('The following PHP extensions are required: %s', 'all-in-one-wp-security-and-firewall'), implode(', ', $php_firewall_required_extensions)) : '',
							'text' => __('Loads security rules before WordPress starts, providing faster protection and blocking threats before they can interact with your WordPress installation.', 'all-in-one-wp-security-and-firewall')
						),
						'is_lock'  => $lock_preload_firewall_rules,
						'label'    => __('Pre-load firewall rules', 'all-in-one-wp-security-and-firewall'),
						'default'  => true,
					),
					...(AIOWPSecurity_Utility::allow_to_write_to_htaccess() ? array(
						array(
							'id'       => 'enable_htaccess_rules',
							'group_id' => 'firewall',
							'type'     => 'checkbox',
							'subtype'  => 'switch',
							'tooltip'  => array(
								'text' => __('Add web-server rules (Apache/LiteSpeed) to block common threats.', 'all-in-one-wp-security-and-firewall')
							),
							'label'    => __('Enable .htaccess rules', 'all-in-one-wp-security-and-firewall'),
							'default'  => true,
						),
					) : array()),
					...(is_multisite() ? array(
						array(
							'id'      => 'apply_settings_to_subsites',
							'type'    => 'checkbox',
							'label'   => __('Apply these settings to all my sites', 'all-in-one-wp-security-and-firewall'),
							'default' => true,
						),
					) : array()),
				),
				'button'   => array(
					'id'    => 'save',
					'label' => __('Save and continue', 'all-in-one-wp-security-and-firewall'),
					'icon'  => 'continue-arrow-right'
				),
			),
			...$tfa_step,
			array(
				'id'             => 'email',
				'type'           => 'email',
				'icon'           => 'mail',
				'title'          => __('Stay in the loop', 'all-in-one-wp-security-and-firewall'),
				'subtitle'       => __('Join our newsletter for latest news, tips and best practices on website security.', 'all-in-one-wp-security-and-firewall') . ' ' . __('Delivered straight to your inbox.', 'all-in-one-wp-security-and-firewall'),
				'fields'         => array(
					array(
						'id'      => 'email_reports_mailinglist',
						'key'     => 'email_reports_mailinglist',
						'type'    => 'email',
						'label'   => __('Email', 'all-in-one-wp-security-and-firewall'),
						'default' => '',
					),
					array(
						'id'      => 'tips_tricks_mailinglist',
						'key'     => 'tips_tricks_mailinglist',
						'type'    => 'checkbox',
						'label'   => __('I agree to receive emails with tips, updates and marketing content.', 'all-in-one-wp-security-and-firewall') . ' ' . __('I understand I can unsubscribe at any time.', 'all-in-one-wp-security-and-firewall'),
						'default' => false,
						'show_privacy_link' => true,
					),
				),
				'button'         => array(
					'id'    => 'save',
					'label' => __('Save and continue', 'all-in-one-wp-security-and-firewall'),
					'icon'  => 'EastRoundedIcon',
				),
			),
			array(
				'id'                     => 'plugins',
				'type'                   => 'plugins',
				'icon'                   => 'plugin',
				'title'                  => __('Recommended for your setup', 'all-in-one-wp-security-and-firewall'),
				'title_conditional'      => array(
					'all_installed'      => __('Best-practice plugins enabled', 'all-in-one-wp-security-and-firewall'),
				),
				'subtitle'               => __('Based on your website configuration, we recommend the following plugins:', 'all-in-one-wp-security-and-firewall'),
				'subtitle_conditional'   => array(
					'all_installed'      => __('Wow, your site already meets all our plugin recommendations, let\'s move on.', 'all-in-one-wp-security-and-firewall'),
				),
				'fields'                 => array(
					array(
						'id'    => 'plugins',
						'type'  => 'plugins'
					),
				),
				'button'                 => array(
					'id'    => 'save',
					'label' => __('Install and continue', 'all-in-one-wp-security-and-firewall'),
					'icon'  => 'EastRoundedIcon',
				),
			),
			...(!$this->is_premium ? array(
				array(
					'id'                 => 'go_premium',
					'type'               => 'go_premium',
					'icon'               => 'bolt',
					'title'              => __('Upgrade to Premium', 'all-in-one-wp-security-and-firewall'),
					'subtitle'           => __('Gain advanced tools for iron-clad security and full control.', 'all-in-one-wp-security-and-firewall'),
					'bullets'            => $last_step_bullets,
					'enable_premium_btn' => true,
					'premium_btn_text' => __('Upgrade to Premium', 'all-in-one-wp-security-and-firewall'),
				),
			) : array()),
			array(
				'id'                   => 'completed',
				'type'                 => 'completed',
				'icon'                 => 'bolt',
				'title'                => __('You\'re all set', 'all-in-one-wp-security-and-firewall'),
				'title_conditional'    => array(
					'isInstalling' => __('Almost done, finalizing...', 'all-in-one-wp-security-and-firewall'),
				),
				'subtitle'             => $this->is_premium ? __('All-in-One Security is now active, and all premium features are unlocked:', 'all-in-one-wp-security-and-firewall') : __('We\'ve activated the essential security features to start protecting your site immediately.', 'all-in-one-wp-security-and-firewall') . ' ' . __('You can explore the dashboard to see your new protection in action and manage your settings.', 'all-in-one-wp-security-and-firewall'),
				'subtitle_conditional' => array(
					'isInstalling' => __('Please Wait...', 'all-in-one-wp-security-and-firewall'),
				),
				'bullets'              => $this->is_premium ? $last_step_bullets : array(array()),
				'button'               => array(
					'id'    => 'finish',
					'label' => __('Go to the dashboard', 'all-in-one-wp-security-and-firewall'),
				),
			),
		);

		return $steps;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			self::PREFIX . '/v1/onboarding',
			'tfa_key_is_valid',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'handle_tfa_key_is_valid'),
				'permission_callback' => 'AIOWPSecurity_Utility_Permissions::has_manage_cap'
			)
		);
	}

	/**
	 * Handle `tfa_key_is_valid` rest request.
	 *
	 * @global AIO_WP_Security_Simba_Two_Factor_Authentication_Plugin $simba_two_factor_authentication
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function handle_tfa_key_is_valid($request) {
		global $simba_two_factor_authentication;

		$nonce = sanitize_text_field($request->get_param('nonce'));

		if (!wp_verify_nonce($nonce, self::PREFIX . '_nonce')) {
			return new WP_REST_Response(
				array(
					'success'         => false,
					'message'         => __('Nonce verification failed.', 'all-in-one-wp-security-and-firewall'),
					'request_success' => true,
				),
				403
			);
		}

		if (!isset($simba_two_factor_authentication) || !isset($simba_two_factor_authentication->get_controllers()['totp'])) {
			return new WP_REST_Response(
				array(
					'success'         => false,
					'request_success' => true,
				),
				500
			);
		}

		$totp_controller = $simba_two_factor_authentication->get_controller('totp');

		$user_id = wp_get_current_user()->ID;
		$user_code = sanitize_text_field($request->get_param('key'));

		if ($totp_controller->check_code_for_user($user_id, $user_code, false)) {
			return new WP_REST_Response(
				array(
					'success'         => true,
					'request_success' => true,
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success'         => false,
					'request_success' => true,
				),
				200
			);
		}
	}

	/**
	 * Checks whether the user wants the settings to also apply to the subsites or just the mainsite.
	 *
	 * @param array $settings Settings data
	 *
	 * @return bool
	 */
	private function apply_settings_to_subsites($settings) {
		if (!is_multisite()) return false;

		foreach ($settings as $setting) {
			if (!isset($setting['id']) || empty($setting['id'])) continue;

			if (!isset($setting['value']) || empty($setting['value'])) {
				$setting['value'] = false;
			}

			if ('apply_settings_to_subsites' === $setting['id']) {
				return (bool) $setting['value'];
			}
		}

		return false;
	}

	/**
	 * Conditionally runs a function for every subsite.
	 *
	 * @global wpdb $wpdb
	 *
	 * @param bool     $apply_settings_to_subsites Whether to run the function for every subsite.
	 * @param callback $apply_settings             The function to conditionally run for every subsite.
	 *
	 * @return void
	 */
	private function conditionally_apply_settings_to_subsites($apply_settings_to_subsites, $apply_settings) {
		if ($apply_settings_to_subsites) {
			global $wpdb;

			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				$apply_settings();
				restore_current_blog();
			}
		} else {
			$apply_settings();
		}
	}

	/**
	 * Get IDs from step_fields array.
	 *
	 * @param array $step_fields Step Fields data
	 *
	 * @return array
	 */
	private function get_step_ids($step_fields) {
		$step_ids = array();
		if (!empty($step_fields)) {
			foreach ($step_fields as $step) {
				$step_ids[] = $step['id'];
			}
		}

		return $step_ids;
	}

	/**
	 * Updates feature settings.
	 *
	 * @global AIO_WP_Security $aio_wp_security
	 * @global AIO_WP_Security_Simba_Two_Factor_Authentication_Plugin $simba_two_factor_authentication
	 * @global WP_Roles $wp_roles
	 *
	 * @param array $settings    Settings data
	 * @param array $step_fields The fields data for the current step
	 *
	 * @return void
	 */
	public function update_step_settings($settings, $step_fields) {
		if (!current_user_can($this->required_capability())) {
			return;
		}

		if (!empty($settings)) {
			global $aio_wp_security;

			$apply_settings_to_subsites = $this->apply_settings_to_subsites($settings);
			$step_ids = $this->get_step_ids($step_fields);

			foreach ($settings as $setting) {
				if (!isset($setting['id']) || empty($setting['id'])) continue;

				if (!in_array($setting['id'], $step_ids)) continue;

				if (!isset($setting['value']) || empty($setting['value'])) {
					$setting['value'] = false;
				}

				$id = (string) $setting['id'];
				$value = ('two_fa_verification_code' === $id) ? $setting['value'] : (bool) $setting['value'];

				if ('prevent_user_enumeration' === $id) {
					$this->conditionally_apply_settings_to_subsites($apply_settings_to_subsites, function() use ($aio_wp_security, $value) {
						$aio_wp_security->configs->set_value('aiowps_prevent_users_enumeration', $value ? '1' : '', true);
					});
				} elseif ('login_lockdown' === $id) {
					$this->conditionally_apply_settings_to_subsites($apply_settings_to_subsites, function() use ($aio_wp_security, $value) {
						$aio_wp_security->configs->set_value('aiowps_enable_login_lockdown', $value ? '1' : '', true);
					});
				} elseif ('force_logout' === $id) {
					$this->conditionally_apply_settings_to_subsites($apply_settings_to_subsites, function() use ($aio_wp_security, $value) {
						$aio_wp_security->configs->set_value('aiowps_enable_forced_logout', $value ? '1' : '', true);
						if ($value) $aio_wp_security->configs->set_value('aiowps_logout_time_period', 60, true);
					});
				} elseif ('disable_php_file_editing' === $id) {
					if ($value ? AIOWPSecurity_Utility::disable_file_edits() : AIOWPSecurity_Utility::enable_file_edits()) {
						// Save settings if no errors.
						$aio_wp_security->configs->set_value('aiowps_disable_file_editing', $value ? '1' : '', true);
					}
				} elseif ('enable_iframe_protection' === $id) {
					$this->conditionally_apply_settings_to_subsites($apply_settings_to_subsites, function() use ($aio_wp_security, $value) {
						$aio_wp_security->configs->set_value('aiowps_prevent_site_display_inside_frame', $value ? '1' : '', true);
					});
				} elseif ('copy_protection' === $id) {
					$this->conditionally_apply_settings_to_subsites($apply_settings_to_subsites, function() use ($aio_wp_security, $value) {
						$aio_wp_security->configs->set_value('aiowps_copy_protection', $value ? '1' : '', true);
					});
				} elseif ('detect_spambots' === $id) {
					$this->conditionally_apply_settings_to_subsites($apply_settings_to_subsites, function() use ($aio_wp_security, $value) {
						$aio_wp_security->configs->set_value('aiowps_enable_spambot_detecting', $value ? '1' : '', true);
						$aio_wp_security->configs->set_value('aiowps_spam_comments_should', $value ? '1' : '', true);
					});
				} elseif ('auto_block_ip_after_3_spam_comments' === $id) {
					$this->conditionally_apply_settings_to_subsites($apply_settings_to_subsites, function() use ($aio_wp_security, $value) {
						$aio_wp_security->configs->set_value('aiowps_enable_autoblock_spam_ip', $value ? '1' : '', true);
						if ($value) $aio_wp_security->configs->set_value('aiowps_spam_ip_min_comments_block', 3, true);
					});
				} elseif ('block_xmlrpc' === $id) {
					$aiowps_firewall_config = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::CONFIG);
					$aiowps_firewall_config->set_value('aiowps_enable_pingback_firewall', $value);
				} elseif ('turn_on_6g_method_blocking' === $id) {
					if ($value) {
						$aiowps_firewall_config = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::CONFIG);

						$aiowps_6g_block_request_methods = array_filter(AIOS_Abstracted_Ids::get_firewall_block_request_methods(), function($block_request_method) {
							return ('PUT' != $block_request_method);
						});

						$aiowps_firewall_config->set_value('aiowps_6g_block_request_methods', $aiowps_6g_block_request_methods);
						$aiowps_firewall_config->set_value('aiowps_6g_block_query', true);
						$aiowps_firewall_config->set_value('aiowps_6g_block_request', true);
						$aiowps_firewall_config->set_value('aiowps_6g_block_referrers', true);
						$aiowps_firewall_config->set_value('aiowps_6g_block_agents', true);

						$aio_wp_security->configs->set_value('aiowps_enable_6g_firewall', '1', true);
					} else {
						AIOWPSecurity_Configure_Settings::turn_off_all_6g_firewall_configs();
						$aio_wp_security->configs->set_value('aiowps_enable_6g_firewall', '', true);
					}
				} elseif ('preload_firewall_rules' === $id) {
					if ($value) {
						if (!AIOWPSecurity_Utility_Firewall::is_firewall_setup()) {
							$firewall_setup = AIOWPSecurity_Firewall_Setup_Notice::get_instance();
							$firewall_setup->do_setup();
						}
					} elseif (AIOWPSecurity_Utility_Firewall::is_firewall_setup()) {
						AIOWPSecurity_Utility_Firewall::remove_firewall();
					}
				} elseif ('enable_htaccess_rules' === $id) {
					$original_options = array(
						'aiowps_enable_basic_firewall' => $aio_wp_security->configs->get_value('aiowps_enable_basic_firewall'),
						'aiowps_max_file_upload_size' => $aio_wp_security->configs->get_value('aiowps_max_file_upload_size'),
						'aiowps_block_debug_log_file_access' => $aio_wp_security->configs->get_value('aiowps_block_debug_log_file_access'),
						'aiowps_disable_index_views' => $aio_wp_security->configs->get_value('aiowps_disable_index_views'),
					);

					$aio_wp_security->configs->set_value('aiowps_enable_basic_firewall', $value ? '1' : '');
					$aio_wp_security->configs->set_value('aiowps_max_file_upload_size', AIOS_FIREWALL_MAX_FILE_UPLOAD_LIMIT_MB);
					$aio_wp_security->configs->set_value('aiowps_block_debug_log_file_access', $value ? '1' : '');
					$aio_wp_security->configs->set_value('aiowps_disable_index_views', $value ? '1' : '');
					$aio_wp_security->configs->save_config();

					$result = AIOWPSecurity_Utility_Htaccess::write_to_htaccess();

					if (!$result) {
						foreach ($original_options as $key => $original_value) {
							$aio_wp_security->configs->set_value($key, $original_value);
						}
						$aio_wp_security->configs->save_config();
					}
				} elseif ('two_fa_verification_code' === $id) {
					global $simba_two_factor_authentication;

					if (isset($simba_two_factor_authentication) && $value['validated']) {
						$user_id = wp_get_current_user()->ID;
						$simba_two_factor_authentication->change_tfa_enabled_status($user_id, 'true');
					}
				} elseif ('tfa_all_roles' === $id && $value && $this->is_premium) {
					global $wp_roles;

					foreach ($wp_roles->role_names as $id => $name) {
						update_option('tfa_required_'.$id, 1);
					}

					if (is_multisite()) {
						update_option('tfa_required__super_admin', 1);
					}
				}
			}
		}
	}
}

// phpcs:enable Squiz.ControlStructures.InlineIfDeclaration.NotSingleLine, PHPCompatibility.Syntax -- This code is only run for php >= 7.4.
