<?php
if (!defined('ABSPATH')) die('No direct access allowed');

if (trait_exists('AIOWPSecurity_Brute_Force_Commands_Trait')) return;

trait AIOWPSecurity_Brute_Force_Commands_Trait {

	/**
	 * Perform saving rename login settings
	 *
	 * @param array $data - the request data contains PHP settings
	 *
	 * @return array|WP_Error
	 */
	public function perform_rename_login_page($data) {
		global $aio_wp_security;

		if (AIOS_Helper::is_updraft_central_request()) {
			if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
				return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
			}
		}

		$success = true;
		$options = array();
		$args = array();

		$aiowps_login_page_slug = '';
		$error = '';

		if ('' == $data['aiowps_login_page_slug'] && isset($data["aiowps_enable_rename_login_page"])) {
			$error = __('Please enter a value for your login page slug.', 'all-in-one-wp-security-and-firewall');
		} elseif ('' != $data['aiowps_login_page_slug']) {
			$aiowps_login_page_slug = sanitize_text_field($data['aiowps_login_page_slug']);
			if ('wp-admin' == $aiowps_login_page_slug) {
				$error = '<br>' . __('You cannot use the value "wp-admin" for your login page slug.', 'all-in-one-wp-security-and-firewall');
			} elseif (preg_match('/[^\p{L}\p{N}_\-]/u', $aiowps_login_page_slug)) {
				$error = '<br>' . __('You must use alphanumeric characters for your login page slug.', 'all-in-one-wp-security-and-firewall');
			}
		}

		if ($error) {
			$success = false;
			$message = $error;
		} else {
			$options['aiowps_enable_rename_login_page'] = isset($data["aiowps_enable_rename_login_page"]) ? '1' : '';
			$options['aiowps_login_page_slug'] = $aiowps_login_page_slug;

			$this->save_settings($options);

			if (get_option('permalink_structure')) {
				$home_url = trailingslashit(home_url());
			} else {
				$home_url = trailingslashit(home_url()) . '?';
			}

			$message = __('The settings have been successfully updated.', 'all-in-one-wp-security-and-firewall');
			$args['badges'] = array("bf-rename-login-page");
			$args['content'] = array('aios-rename-login-notice' => $aio_wp_security->include_template('wp-admin/brute-force/partials/rename-login-notice.php', true, array('home_url' => $home_url)));
			$args['extra_args'] = array('logout_url' => $this->get_logout_url($options));
		}

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * Handles the AJAX request to enable or configure cookie-based brute force prevention.
	 *
	 * @param array $data The data received from the AJAX request.
	 *
	 * @return array|WP_Error The response containing the status, message, and badge.
	 */
	public function perform_cookie_based_brute_force_prevention($data) {
		global $aio_wp_security;

		if (AIOS_Helper::is_updraft_central_request()) {
			if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
				return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
			}
		}

		$options = array();
		$values = array();
		$info = array();

		$success = true;
		$message = '';
		$result = '';

		if (isset($data['aiowps_enable_brute_force_attack_prevention'])) {
			$brute_force_feature_secret_word = sanitize_text_field($data['aiowps_brute_force_secret_word']);
			$redirect_url = sanitize_text_field($data['aiowps_cookie_based_brute_force_redirect_url']);
			if (empty($brute_force_feature_secret_word)) {
				$brute_force_feature_secret_word = AIOS_DEFAULT_BRUTE_FORCE_FEATURE_SECRET_WORD;
				$info[] = __('You entered an invalid value for the secret word.', 'all-in-one-wp-security-and-firewall'). ' ' . __('It has been set to the default value.', 'all-in-one-wp-security-and-firewall');
			} elseif (!ctype_alnum($brute_force_feature_secret_word)) {
				$message = '<p>' . __('Settings have not been saved - your secret word must consist only of alphanumeric characters i.e., letters and/or numbers only.', 'all-in-one-wp-security-and-firewall') . '</p>';
				$success = false;
			}

			if (filter_var($redirect_url, FILTER_VALIDATE_URL)) {
				$redirect_url = esc_url_raw($redirect_url);
			} else {
				$redirect_url = 'http://127.0.0.1';
				$info[] = __('You entered an invalid value for the redirect url.', 'all-in-one-wp-security-and-firewall'). ' ' . __('It has been set to the default value.', 'all-in-one-wp-security-and-firewall');
			}

			$options['aiowps_cookie_based_brute_force_redirect_url'] = $redirect_url;

			if ($success) {
				$options['aiowps_enable_brute_force_attack_prevention'] = '1';
				$options['aiowps_brute_force_secret_word'] = $brute_force_feature_secret_word;

				$result = '<p>' . __('You have successfully enabled the cookie based brute force prevention feature', 'all-in-one-wp-security-and-firewall') . '</p>';
				$result .= '<p>' . __('From now on you will need to log into your WP Admin using the following URL:', 'all-in-one-wp-security-and-firewall') . '</p>';
				$result .= '<p><strong>'.AIOWPSEC_WP_URL.'/?'.esc_html($brute_force_feature_secret_word).'=1</strong></p>';
				$result .= '<p>' . __('It is important that you save this URL value somewhere in case you forget it, OR,', 'all-in-one-wp-security-and-firewall') . '</p>';
				$result .= '<p>' . sprintf(__('simply remember to add a "?%s=1" to your current site URL address.', 'all-in-one-wp-security-and-firewall'), esc_html($brute_force_feature_secret_word)) . '</p>';
				AIOWPSecurity_Utility::set_cookie_value(AIOWPSecurity_Utility::get_brute_force_secret_cookie_name(), AIOS_Helper::get_hash($brute_force_feature_secret_word));
			}
		} else {
			$options['aiowps_enable_brute_force_attack_prevention'] = '';
			$message = __('You have successfully saved cookie based brute force prevention feature settings.', 'all-in-one-wp-security-and-firewall');
			$brute_force_feature_secret_word = $aio_wp_security->configs->get_value('aiowps_brute_force_secret_word');
			$redirect_url = $aio_wp_security->configs->get_value('aiowps_cookie_based_brute_force_redirect_url');
		}

		$options['aiowps_brute_force_attack_prevention_pw_protected_exception'] = isset($data['aiowps_brute_force_attack_prevention_pw_protected_exception']) ? '1' : '';
		$options['aiowps_brute_force_attack_prevention_ajax_exception'] = isset($data['aiowps_brute_force_attack_prevention_ajax_exception']) ? '1' : '';

		if ($success) {
			$this->save_settings($options);

			AIOWPSecurity_Configure_Settings::set_cookie_based_bruteforce_firewall_configs();

			$message = __('The settings have been successfully updated.', 'all-in-one-wp-security-and-firewall');
			$values['aiowps_brute_force_secret_word'] = $brute_force_feature_secret_word;
			$values['aiowps_cookie_based_brute_force_redirect_url'] = $redirect_url;
		}
		$content = array(
			'aios-brute-force-info-box' => $result
		);

		$badges = array("firewall-enable-brute-force-attack-prevention");

		$args = array(
			'badges' => $badges,
			'info' => $info,
			'values' => $values,
			'content' => $content
		);

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * Handles the AJAX request for performing cookie test.
	 *
	 * @return array The response containing the status, message, and badge.
	 */
	public function perform_cookie_test() {
		global $aio_wp_security;

		$success = true;

		$random_suffix = AIOWPSecurity_Utility::generate_alpha_numeric_random_string(10);
		$test_cookie_name = 'aiowps_cookie_test_'.$random_suffix;
		$aio_wp_security->configs->set_value('aiowps_cookie_brute_test', $test_cookie_name, true);
		$set_cookie = AIOWPSecurity_Utility::set_cookie_value($test_cookie_name, '1');
		$aiowps_cookie_test_success = '';
		$args = array();

		if ($set_cookie) {
			$aiowps_cookie_test_success = '1';
			$message = __('The cookie test was successful, you can now enable this feature.', 'all-in-one-wp-security-and-firewall');
			$result = '<div class="aio_green_box"><p>' . __('The cookie test was successful, you can now enable this feature.', 'all-in-one-wp-security-and-firewall') . '</p></div>';
		} else {
			$success = false;
			$message = __('The cookie test failed.', 'all-in-one-wp-security-and-firewall')  .' '. __('Consequently, this feature cannot be used on this site.', 'all-in-one-wp-security-and-firewall');
			$result = '<div class="aio_red_box"><p>' . __('The cookie test failed on this server.', 'all-in-one-wp-security-and-firewall') .' '. __('Consequently, this feature cannot be used on this site.', 'all-in-one-wp-security-and-firewall') . '</p></div>';
		}

		$this->save_settings(array('aiowps_cookie_test_success' => $aiowps_cookie_test_success)); // save the value
		$args['content'] = array(
			'aios-perform-cookie-test-div' => $this->get_perform_cookie_test_content(),
			'cookie-test-result-div' => $result
		);

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * Handles the AJAX request to enable or configure login whitelist settings.
	 *
	 * @param array $data The data received from the AJAX request.
	 *
	 * @return array The response containing the status, message, and badge.
	 */
	public function perform_login_whitelist_settings($data) {
		global $aio_wp_security;

		$success = true;
		$options = array();
		$message = '';

		if (!empty($data['aiowps_allowed_ip_addresses'])) {
			$ip_addresses = sanitize_textarea_field(stripslashes($data['aiowps_allowed_ip_addresses']));
			$ip_list_array = AIOWPSecurity_Utility_IP::create_ip_list_array_from_string_with_newline($ip_addresses);
			$validated_ip_list_array = AIOWPSecurity_Utility_IP::validate_ip_list($ip_list_array, 'whitelist');
			if (is_wp_error($validated_ip_list_array)) {
				$result = -1;
				$success = false;
				$message = nl2br($validated_ip_list_array->get_error_message());
			} else {
				$result = 1;
				$whitelist_ip_data = implode("\n", $validated_ip_list_array);
				$options['aiowps_allowed_ip_addresses'] = $whitelist_ip_data;
			}
		} else {
			$result = 1;
			$options['aiowps_allowed_ip_addresses'] = ''; // Clear the IP address config value
		}

		if (1 == $result) {
			$options['aiowps_enable_whitelisting'] = isset($data["aiowps_enable_whitelisting"]) ? '1' : '';
			if ('1' == $aio_wp_security->configs->get_value('aiowps_is_login_whitelist_disabled_on_upgrade')) {
				$aio_wp_security->configs->delete_value('aiowps_is_login_whitelist_disabled_on_upgrade');
			}
			$this->save_settings($options);
		}

		$args = array(
			'badges' => array('whitelist-manager-ip-login-whitelisting')
		);

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * Handles the AJAX request to enable or configure honeypot brute force settings.
	 *
	 * @param array $data The data received from the AJAX request.
	 *
	 * @return array|WP_Error The response containing the status, message, and badge.
	 */
	public function perform_honeypot_settings($data) {
		if (AIOS_Helper::is_updraft_central_request()) {
			if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
				return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
			}
		}

		$options = array();
		// Save all the form values to the options
		$options['aiowps_enable_login_honeypot'] = isset($data["aiowps_enable_login_honeypot"]) ? '1' : '';
		$options['aiowps_enable_registration_honeypot'] = isset($data["aiowps_enable_registration_honeypot"]) ? '1' : '';

		$this->save_settings($options);

		$args = array(
			'badges' => array('login-honeypot', 'registration-honeypot')
		);

		return $this->handle_response(true, '', $args);
	}

	/**
	 * Handles the AJAX request to enable or configure captcha settings.
	 *
	 * @param array $data The data received from the AJAX request.
	 *
	 * @return array|WP_Error The response containing the status, message, and badge.
	 */
	public function perform_captcha_settings($data) {
		global $aio_wp_security;

		if (AIOS_Helper::is_updraft_central_request()) {
			if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
				return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
			}
		}

		$captcha_themes = $aio_wp_security->captcha_obj->get_captcha_themes();
		$supported_captchas = $aio_wp_security->captcha_obj->get_supported_captchas();
		$options = array();

		$default_captcha = isset($data['aiowps_default_captcha']) ? sanitize_text_field($data['aiowps_default_captcha']) : '';

		$default_captcha = array_key_exists($default_captcha, $supported_captchas) ? $default_captcha : 'none';

		$options['aiowps_default_captcha'] = $default_captcha;

		// Save all the form values to the options
		$random_20_digit_string = AIOWPSecurity_Utility::generate_alpha_numeric_random_string(20); // Generate random 20 char string for use during CAPTCHA encode/decode
		$options['aiowps_captcha_secret_key'] = $random_20_digit_string;
		$options['aiowps_enable_login_captcha'] = isset($data["aiowps_enable_login_captcha"]) ? '1' : '';
		$options['aiowps_enable_registration_page_captcha'] = isset($data["aiowps_enable_registration_page_captcha"]) ? '1' : '';
		$options['aiowps_enable_comment_captcha'] = isset($data["aiowps_enable_comment_captcha"]) ? '1' : '';
		$options['aiowps_enable_bp_register_captcha'] = isset($data["aiowps_enable_bp_register_captcha"]) ? '1' : '';
		$options['aiowps_enable_bbp_new_topic_captcha'] = isset($data["aiowps_enable_bbp_new_topic_captcha"]) ? '1' : '';
		$options['aiowps_enable_woo_login_captcha'] = isset($data["aiowps_enable_woo_login_captcha"]) ? '1' : '';
		$options['aiowps_enable_woo_register_captcha'] = isset($data["aiowps_enable_woo_register_captcha"]) ? '1' : '';
		$options['aiowps_enable_woo_lostpassword_captcha'] = isset($data["aiowps_enable_woo_lostpassword_captcha"]) ? '1' : '';
		$options['aiowps_enable_woo_checkout_captcha'] = isset($data["aiowps_enable_woo_checkout_captcha"]) ? '1' : '';
		$options['aiowps_enable_custom_login_captcha'] = isset($data["aiowps_enable_custom_login_captcha"]) ? '1' : '';
		$options['aiowps_enable_lost_password_captcha'] = isset($data["aiowps_enable_lost_password_captcha"]) ? '1' : '';
		$options['aiowps_enable_contact_form_7_captcha'] = isset($data["aiowps_enable_contact_form_7_captcha"]) ? '1' : '';
		$options['aiowps_enable_password_protected_captcha'] = isset($data["aiowps_enable_password_protected_captcha"]) ? '1' : '';

		$options['aiowps_turnstile_site_key'] = sanitize_text_field(stripslashes($data['aiowps_turnstile_site_key']));
		$options['aiowps_recaptcha_site_key'] = sanitize_text_field(stripslashes($data['aiowps_recaptcha_site_key']));

		$turnstile_theme = isset($data['aiowps_turnstile_theme']) ? sanitize_text_field($data['aiowps_turnstile_theme']) : '';
		$turnstile_theme = array_key_exists($turnstile_theme, $captcha_themes) ? $turnstile_theme : 'auto';
		$options['aiowps_turnstile_theme'] = $turnstile_theme;

		// If secret key is masked then don't resave it
		$turnstile_secret_key = sanitize_text_field($data['aiowps_turnstile_secret_key']);
		if (strpos($turnstile_secret_key, '********') === false) {
			$options['aiowps_turnstile_secret_key'] = $turnstile_secret_key;
		}

		// If secret key is masked then don't resave it
		$recaptcha_secret_key = sanitize_text_field($data['aiowps_recaptcha_secret_key']);
		if (strpos($recaptcha_secret_key, '********') === false) {
			$options['aiowps_recaptcha_secret_key'] = $recaptcha_secret_key;
		}

		if ('google-recaptcha-v2' == $aio_wp_security->configs->get_value('aiowps_default_captcha') && false === $aio_wp_security->captcha_obj->google_recaptcha_verify_configuration($aio_wp_security->configs->get_value('aiowps_recaptcha_site_key'), $aio_wp_security->configs->get_value('aiowps_recaptcha_secret_key'))) {
			$options['aios_google_recaptcha_invalid_configuration'] = '1';
		} elseif ('1' == $aio_wp_security->configs->get_value('aios_google_recaptcha_invalid_configuration')) {
			$aio_wp_security->configs->delete_value('aios_google_recaptcha_invalid_configuration');
		}

		$this->save_settings($options);

		$success = false;
		$message = '';
		if ('cloudflare-turnstile' == $aio_wp_security->configs->get_value('aiowps_default_captcha') && false === $aio_wp_security->captcha_obj->cloudflare_turnstile_verify_configuration($aio_wp_security->configs->get_value('aiowps_turnstile_site_key'), $aio_wp_security->configs->get_value('aiowps_turnstile_secret_key'))) {
			$message = __('Your Cloudflare Turnstile configuration is invalid.', 'all-in-one-wp-security-and-firewall').' '.__('Please enter the correct Cloudflare Turnstile keys below to use the Turnstile feature.', 'all-in-one-wp-security-and-firewall');
		} elseif ('google-recaptcha-v2' == $aio_wp_security->configs->get_value('aiowps_default_captcha') && '1' == $aio_wp_security->configs->get_value('aios_google_recaptcha_invalid_configuration')) {
			$message = __('Your Google reCAPTCHA configuration is invalid.', 'all-in-one-wp-security-and-firewall').' '.__('Please enter the correct reCAPTCHA keys below to use the reCAPTCHA feature.', 'all-in-one-wp-security-and-firewall');
		} else {
			$success = true;
		}

		$features = array(
			"user-login-captcha",
			"user-registration-captcha",
			"lost-password-captcha",
			"custom-login-captcha",
			"comment-form-captcha",
			"password_protected-captcha",
		);

		if (AIOWPSecurity_Utility::is_woocommerce_plugin_active()) {
			$woocommerce_features = array(
				"woo-login-captcha",
				"woo-lostpassword-captcha",
				"woo-register-captcha",
				"woo-checkout-captcha",
			);
			$features = array_merge($features, $woocommerce_features);
		}

		if (AIOWPSecurity_Utility::is_buddypress_plugin_active()) {
			$features[] = "bp-register-captcha";
		}

		if (AIOWPSecurity_Utility::is_bbpress_plugin_active()) {
			$features[] = "bbp-new-topic-captcha";
		}

		if (AIOWPSecurity_Utility::is_contact_form_7_plugin_active()) {
			$features[] = "contact-form-7-captcha";
		}

		$args = array(
			'badges' => $features
		);

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * Handles the AJAX request to enable or configure 404 detection and settings.
	 *
	 * @param array $data The data received from the AJAX request.
	 *
	 * @return array The response containing the status, message, and badge.
	 */
	public function perform_404_settings($data) {

		$options = array();
		$info = array();
		$values = array();

		$options['aiowps_enable_404_logging'] = isset($data["aiowps_enable_404_IP_lockout"]) ? '1' : ''; //the "aiowps_enable_404_IP_lockout" checkbox currently controls both the 404 lockout and 404 logging
		$options['aiowps_enable_404_IP_lockout'] = isset($data["aiowps_enable_404_IP_lockout"]) ? '1' : '';

		$lockout_time_length = isset($data['aiowps_404_lockout_time_length']) ? sanitize_text_field($data['aiowps_404_lockout_time_length']) : '';
		$redirect_url = isset($data['aiowps_404_lock_redirect_url']) ? sanitize_text_field(trim($data['aiowps_404_lock_redirect_url'])) : '';

		if (isset($data["aiowps_enable_404_IP_lockout"])) {
			if (!is_numeric($lockout_time_length) || $lockout_time_length < 1) {
				$info[] = __('You entered a non numeric or negative value for the lockout time length field.', 'all-in-one-wp-security-and-firewall'). ' ' . __('It has been set to the default value.', 'all-in-one-wp-security-and-firewall');
				$lockout_time_length = '60'; // Set it to the default value for this field
			}

			if ('' == $redirect_url || '' == esc_url($redirect_url, array('http', 'https'))) {
				$info[] = __('You entered an incorrect format for the "Redirect URL" field.', 'all-in-one-wp-security-and-firewall') . ' ' . __('It has been set to the default value.', 'all-in-one-wp-security-and-firewall');
				$redirect_url = 'http://127.0.0.1';
			}
		}

		$options['aiowps_404_lockout_time_length'] = absint($lockout_time_length);
		$options['aiowps_404_lock_redirect_url'] = $redirect_url;
		$this->save_settings($options);

		$badges = array("firewall-enable-404-blocking");
		$values['aiowps_404_lockout_time_length'] = $lockout_time_length;
		$values['aiowps_404_lock_redirect_url'] = $redirect_url;

		$args = array(
			'badges' => $badges,
			'info' => $info,
			'values' => $values
		);

		return $this->handle_response(true, '', $args);
	}

	/**
	 * Handles the AJAX request to clear 404 logs.
	 *
	 * @return array The response containing the status, message, and badge.
	 */
	public function perform_delete_all_404_event_records() {
		global $aio_wp_security, $wpdb;

		$success = true;
		$events_table_name = AIOWPSEC_TBL_EVENTS;
		//Delete all 404 records from the events table
		$where = array('event_type' => '404');
		$result = $wpdb->delete($events_table_name, $where);

		if (false === $result) {
			$error = empty($wpdb->last_error) ? '' : $wpdb->last_error;
			$aio_wp_security->debug_logger->log_debug("404 Detection Feature - Delete all 404 event logs operation failed. $error", 4);
			$success = false;
			$message = __('404 Detection Feature - The operation to delete all the 404 event logs failed', 'all-in-one-wp-security-and-firewall');
		} else {
			$message = __('All 404 event logs were deleted from the database successfully.', 'all-in-one-wp-security-and-firewall');
		}

		return $this->handle_response($success, $message);
	}

	/**
	 * Get the content for performing a cookie test.
	 *
	 * This method checks if the cookie test is successful or if the brute-force attack prevention feature is already enabled.
	 * If either condition is true, it returns an empty string. Otherwise, it displays a message prompting the user to perform
	 * a cookie test before enabling the feature, along with a button to initiate the test.
	 *
	 * @return string The HTML content for the cookie test section.
	 */
	private function get_perform_cookie_test_content() {
		global $aio_wp_security;
		$cookie_test_value = $aio_wp_security->configs->get_value('aiowps_cookie_test_success');

		if ('1' == $cookie_test_value || '1' == $aio_wp_security->configs->get_value('aiowps_enable_brute_force_attack_prevention')) {
			return '';
		} else {
			return $aio_wp_security->include_template('wp-admin/brute-force/partials/cookie-test-container.php', true);
		}
	}

	/**
	 * Retrieve settings for the rename login page feature.
	 *
	 * @return array Data for the rename login page feature.
	 */
	public function get_rename_login_page_data() {
		global $aio_wp_security;

		if (get_option('permalink_structure')) {
			$home_url = trailingslashit(home_url());
		} else {
			$home_url = trailingslashit(home_url()) . '?';
		}

		$aiowps_enable_rename_login_page = $aio_wp_security->configs->get_value('aiowps_enable_rename_login_page');
		$aiowps_login_page_slug = $aio_wp_security->configs->get_value('aiowps_login_page_slug');

		return array(
			'home_url' => $home_url,
			'aiowps_enable_rename_login_page' => $aiowps_enable_rename_login_page,
			'aiowps_login_page_slug' => $aiowps_login_page_slug,
		);
	}

	/**
	 * Retrieve settings for the honeypot feature.
	 *
	 * @return array Data for the honeypot feature.
	 */
	public function get_honeypot_data() {
		global $aio_wp_security;

		$aiowps_enable_login_honeypot = $aio_wp_security->configs->get_value('aiowps_enable_login_honeypot');
		$aiowps_enable_registration_honeypot = $aio_wp_security->configs->get_value('aiowps_enable_registration_honeypot');

		return array(
			'aiowps_enable_login_honeypot' => $aiowps_enable_login_honeypot,
			'aiowps_enable_registration_honeypot' => $aiowps_enable_registration_honeypot,
		);
	}

	/**
	 * Retrieve settings for the cookie-based brute force protection feature.
	 *
	 * @return array Data for the cookie-based brute force protection feature.
	 */
	public function get_cookie_based_brute_force_data() {
		global $aio_wp_security;

		$aiowps_cookie_test_success = $aio_wp_security->configs->get_value('aiowps_cookie_test_success');
		$aiowps_enable_brute_force_attack_prevention = $aio_wp_security->configs->get_value('aiowps_enable_brute_force_attack_prevention');
		$aiowps_brute_force_secret_word = $aio_wp_security->configs->get_value('aiowps_brute_force_secret_word');
		$aiowps_cookie_based_brute_force_redirect_url = $aio_wp_security->configs->get_value('aiowps_cookie_based_brute_force_redirect_url');
		$aiowps_brute_force_attack_prevention_pw_protected_exception = $aio_wp_security->configs->get_value('aiowps_brute_force_attack_prevention_pw_protected_exception');
		$aiowps_brute_force_attack_prevention_ajax_exception = $aio_wp_security->configs->get_value('aiowps_brute_force_attack_prevention_ajax_exception');

		return array(
			'aiowps_cookie_test_success' => $aiowps_cookie_test_success,
			'aiowps_enable_brute_force_attack_prevention' => $aiowps_enable_brute_force_attack_prevention,
			'aiowps_brute_force_secret_word' => $aiowps_brute_force_secret_word,
			'aiowps_cookie_based_brute_force_redirect_url' => $aiowps_cookie_based_brute_force_redirect_url,
			'aiowps_brute_force_attack_prevention_pw_protected_exception' => $aiowps_brute_force_attack_prevention_pw_protected_exception,
			'aiowps_brute_force_attack_prevention_ajax_exception' => $aiowps_brute_force_attack_prevention_ajax_exception,
		);
	}

	/**
	 * Retrieve settings for the CAPTCHA feature.
	 *
	 * @return array Data for the CAPTCHA feature.
	 */
	public function get_captcha_settings_data() {
		global $aio_wp_security;

		$supported_captchas = $aio_wp_security->captcha_obj->get_supported_captchas();
		$captcha_themes = $aio_wp_security->captcha_obj->get_captcha_themes();

		$aiowps_default_captcha = $aio_wp_security->configs->get_value('aiowps_default_captcha');

		$captcha_theme = 'auto';
		if ('cloudflare-turnstile' === $aiowps_default_captcha) {
			$captcha_theme = $aio_wp_security->configs->get_value('aiowps_turnstile_theme');
		}

		$aiowps_turnstile_site_key = $aio_wp_security->configs->get_value('aiowps_turnstile_site_key');
		$aiowps_turnstile_secret_key = $aio_wp_security->configs->get_value('aiowps_turnstile_secret_key');
		$cloudflare_turnstile_verify_configuration = $aio_wp_security->captcha_obj->cloudflare_turnstile_verify_configuration($aiowps_turnstile_site_key, $aiowps_turnstile_secret_key);
		$aios_google_recaptcha_invalid_configuration = $aio_wp_security->configs->get_value('aios_google_recaptcha_invalid_configuration');
		$aiowps_recaptcha_site_key = $aio_wp_security->configs->get_value('aiowps_recaptcha_site_key');
		$aiowps_recaptcha_secret_key = $aio_wp_security->configs->get_value('aiowps_recaptcha_secret_key');

		$is_woocommerce_plugin_active = AIOWPSecurity_Utility::is_woocommerce_plugin_active();
		$is_buddypress_plugin_active = AIOWPSecurity_Utility::is_buddypress_plugin_active();
		$is_bbpress_plugin_active = AIOWPSecurity_Utility::is_bbpress_plugin_active();
		$is_contact_form_7_plugin_active = AIOWPSecurity_Utility::is_contact_form_7_plugin_active();

		$aiowps_enable_login_captcha = $aio_wp_security->configs->get_value('aiowps_enable_login_captcha');
		$aiowps_enable_registration_page_captcha = $aio_wp_security->configs->get_value('aiowps_enable_registration_page_captcha');
		$aiowps_enable_lost_password_captcha = $aio_wp_security->configs->get_value('aiowps_enable_lost_password_captcha');
		$aiowps_enable_custom_login_captcha = $aio_wp_security->configs->get_value('aiowps_enable_custom_login_captcha');
		$aiowps_enable_comment_captcha = $aio_wp_security->configs->get_value('aiowps_enable_comment_captcha');
		$aiowps_enable_password_protected_captcha = $aio_wp_security->configs->get_value('aiowps_enable_password_protected_captcha');

		$aiowps_enable_woo_login_captcha = $aio_wp_security->configs->get_value('aiowps_enable_woo_login_captcha');
		$aiowps_enable_woo_lostpassword_captcha = $aio_wp_security->configs->get_value('aiowps_enable_woo_lostpassword_captcha');
		$aiowps_enable_woo_register_captcha = $aio_wp_security->configs->get_value('aiowps_enable_woo_register_captcha');
		$is_enabled_guest_checkout = ('yes' == get_option('woocommerce_enable_guest_checkout')) ? 1 : 0;
		$aiowps_enable_woo_checkout_captcha = $aio_wp_security->configs->get_value('aiowps_enable_woo_checkout_captcha');

		$aiowps_enable_bp_register_captcha = $aio_wp_security->configs->get_value('aiowps_enable_bp_register_captcha');
		$aiowps_enable_bbp_new_topic_captcha = $aio_wp_security->configs->get_value('aiowps_enable_bbp_new_topic_captcha');
		$aiowps_enable_contact_form_7_captcha = $aio_wp_security->configs->get_value('aiowps_enable_contact_form_7_captcha');
		$aiowps_captcha_shortcode = AIOWPSEC_CAPTCHA_SHORTCODE;

		return array(
			'supported_captchas' => $supported_captchas,
			'captcha_themes' => $captcha_themes,
			'captcha_theme' => $captcha_theme,
			'aiowps_default_captcha' => $aiowps_default_captcha,
			'aiowps_turnstile_site_key' => $aiowps_turnstile_site_key,
			'aiowps_turnstile_secret_key' => AIOWPSecurity_Utility::mask_string($aiowps_turnstile_secret_key),
			'cloudflare_turnstile_verify_configuration' => $cloudflare_turnstile_verify_configuration,
			'aios_google_recaptcha_invalid_configuration' => $aios_google_recaptcha_invalid_configuration,
			'aiowps_recaptcha_site_key' => $aiowps_recaptcha_site_key,
			'aiowps_recaptcha_secret_key' => $aiowps_recaptcha_secret_key,
			'is_woocommerce_plugin_active' => $is_woocommerce_plugin_active,
			'is_buddypress_plugin_active' => $is_buddypress_plugin_active,
			'is_bbpress_plugin_active' => $is_bbpress_plugin_active,
			'is_contact_form_7_plugin_active' => $is_contact_form_7_plugin_active,
			'is_other_form_plugins_active' => AIOWPSecurity_Utility::is_other_form_plugins_active(),
			'aiowps_enable_login_captcha' => $aiowps_enable_login_captcha,
			'aiowps_enable_registration_page_captcha' => $aiowps_enable_registration_page_captcha,
			'aiowps_enable_lost_password_captcha' => $aiowps_enable_lost_password_captcha,
			'aiowps_enable_custom_login_captcha' => $aiowps_enable_custom_login_captcha,
			'aiowps_enable_comment_captcha' => $aiowps_enable_comment_captcha,
			'aiowps_enable_password_protected_captcha' => $aiowps_enable_password_protected_captcha,
			'aiowps_enable_woo_login_captcha' => $aiowps_enable_woo_login_captcha,
			'aiowps_enable_woo_lostpassword_captcha' => $aiowps_enable_woo_lostpassword_captcha,
			'aiowps_enable_woo_register_captcha' => $aiowps_enable_woo_register_captcha,
			'is_enabled_guest_checkout' => $is_enabled_guest_checkout,
			'aiowps_enable_woo_checkout_captcha' => $aiowps_enable_woo_checkout_captcha,
			'aiowps_enable_bp_register_captcha' => $aiowps_enable_bp_register_captcha,
			'aiowps_enable_bbp_new_topic_captcha' => $aiowps_enable_bbp_new_topic_captcha,
			'aiowps_enable_contact_form_7_captcha' => $aiowps_enable_contact_form_7_captcha,
			'aiowps_captcha_shortcode' => $aiowps_captcha_shortcode,
		);
	}

	/**
	 * Construct the logout URL based on whether the rename login page feature is enabled and the provided login slug.
	 *
	 * @param array $options Updated plugin options that affect this setting
	 *
	 * @return string The constructed logout URL with the appropriate nonce for logout action.
	 */
	private function get_logout_url($options) {
		$rename_page_enabled = '1' === $options['aiowps_enable_rename_login_page'];
		$login_slug = $options['aiowps_login_page_slug'];
		
		if ($rename_page_enabled && !empty($login_slug)) {
			if (get_option('permalink_structure')) {
				$base_login_url = trailingslashit(home_url($login_slug));
			} else {
				$base_login_url = trailingslashit(home_url()) . '?' . $login_slug;
			}
		} else {
			$base_login_url = trailingslashit(site_url()) . 'wp-login.php';
		}

		$action_url = add_query_arg('action', 'logout', $base_login_url);

		return esc_url_raw(html_entity_decode(wp_nonce_url($action_url, 'log-out')));
	}
}
