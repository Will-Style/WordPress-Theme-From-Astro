<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (trait_exists('AIOWPSecurity_Firewall_Commands_Trait')) return;

trait AIOWPSecurity_Firewall_Commands_Trait {

	/**
	 * Perform saving php firewall settings
	 *
	 * @param array $data - the request data contains PHP settings
	 *
	 * @return array - containing a status and message
	 */
	public function perform_php_firewall_settings($data) {
		global $aio_wp_security;

		$aiowps_firewall_config = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::CONFIG);

		$options = array();

		$enable_pingback = isset($data["aiowps_enable_pingback_firewall"]);
		$info = array();

		// Save settings
		$aiowps_firewall_config->set_value('aiowps_enable_pingback_firewall', $enable_pingback);
		$options['aiowps_disable_xmlrpc_pingback_methods'] = isset($data["aiowps_disable_xmlrpc_pingback_methods"]) ? '1' : ''; //this disables only pingback methods of xmlrpc but leaves other methods so that Jetpack and other apps will still work
		$options['aiowps_disable_rss_and_atom_feeds'] = isset($data['aiowps_disable_rss_and_atom_feeds']) ? '1' : '';
		$aiowps_firewall_config->set_value('aiowps_forbid_proxy_comments', isset($data['aiowps_forbid_proxy_comments']));
		$aiowps_firewall_config->set_value('aiowps_deny_bad_query_strings', isset($data['aiowps_deny_bad_query_strings']));
		$aiowps_firewall_config->set_value('aiowps_advanced_char_string_filter', isset($data['aiowps_advanced_char_string_filter']));

		$block_request_methods = array_map('strtolower', AIOS_Abstracted_Ids::get_firewall_block_request_methods());
		$current_request_methods_settings = $aiowps_firewall_config->get_value('aiowps_6g_block_request_methods');
		$current_other_settings = array(
			$aiowps_firewall_config->get_value('aiowps_6g_block_query'),
			$aiowps_firewall_config->get_value('aiowps_6g_block_request'),
			$aiowps_firewall_config->get_value('aiowps_6g_block_referrers'),
			$aiowps_firewall_config->get_value('aiowps_6g_block_agents'),
		);

		$are_methods_set = !empty($current_request_methods_settings);
		$are_others_set = array_reduce($current_other_settings, function($carry, $item) {
			return $carry || $item;
		});

		if (($are_methods_set || $are_others_set) && '1' !== $aio_wp_security->configs->get_value('aiowps_enable_6g_firewall')) {
			$options['aiowps_enable_6g_firewall'] = '1';
		}

		if (isset($data['aiowps_enable_6g_firewall'])) {
			$aiowps_6g_block_request_methods = array_filter(AIOS_Abstracted_Ids::get_firewall_block_request_methods(), function($block_request_method) {
				return ('PUT' != $block_request_method);
			});

			if (false === $are_methods_set && false === $are_others_set) {
				$aiowps_firewall_config->set_value('aiowps_6g_block_request_methods', $aiowps_6g_block_request_methods);
				$aiowps_firewall_config->set_value('aiowps_6g_block_query', true);
				$aiowps_firewall_config->set_value('aiowps_6g_block_request', true);
				$aiowps_firewall_config->set_value('aiowps_6g_block_referrers', true);
				$aiowps_firewall_config->set_value('aiowps_6g_block_agents', true);
			} else {
				$methods = array();

				foreach ($block_request_methods as $block_request_method) {
					if (isset($data['aiowps_block_request_method_'.$block_request_method])) {
						$methods[] = strtoupper($block_request_method);
					}
				}

				$aiowps_firewall_config->set_value('aiowps_6g_block_request_methods', $methods);
				$aiowps_firewall_config->set_value('aiowps_6g_block_query', isset($data['aiowps_block_query']));
				$aiowps_firewall_config->set_value('aiowps_6g_block_request', isset($data['aiowps_block_request']));
				$aiowps_firewall_config->set_value('aiowps_6g_block_referrers', isset($data['aiowps_block_refs']));
				$aiowps_firewall_config->set_value('aiowps_6g_block_agents', isset($data['aiowps_block_agents']));
			}

			$options['aiowps_enable_6g_firewall'] = '1';

			//shows the success notice
		} else {
			AIOWPSecurity_Configure_Settings::turn_off_all_6g_firewall_configs();
			$options['aiowps_enable_6g_firewall'] = '';
		}

		$aiowps_firewall_config->set_value('aiowps_ban_post_blank_headers', isset($data['aiowps_ban_post_blank_headers']));

		if (isset($data['aiowps_block_fake_googlebots'])) {
			$validated_ip_list_array = AIOWPSecurity_Utility::get_googlebot_ip_ranges();

			if (is_wp_error($validated_ip_list_array)) {
				$info[] = __('The attempt to save the \'Block fake Googlebots\' settings failed, because it was not possible to validate the Googlebot IP addresses:', 'all-in-one-wp-security-and-firewall') . ' ' . $validated_ip_list_array->get_error_message();
			} else {
				$aiowps_firewall_config->set_value('aiowps_block_fake_googlebots', true);
				$aiowps_firewall_config->set_value('aiowps_googlebot_ip_ranges', $validated_ip_list_array);
			}
		} else {
			$aiowps_firewall_config->set_value('aiowps_block_fake_googlebots', false);
		}
		$options['aiowps_disallow_unauthorized_rest_requests'] = isset($data["aiowps_disallow_unauthorized_rest_requests"]) ? '1' : '';
		
		$aios_whitelisted_rest_routes = array();
		$route_namespaces = AIOWPSecurity_Utility::get_rest_namespaces();
		foreach ($route_namespaces as $route_namespace) {
			if (isset($data['aios_whitelisted_rest_routes_'.str_replace('-', '_', $route_namespace)])) {
				$aios_whitelisted_rest_routes[] = $route_namespace;
			}
		}
		$options['aios_whitelisted_rest_routes'] = $aios_whitelisted_rest_routes;
		
		$aios_roles_disallowed_rest_requests = array();
		$user_roles = AIOWPSecurity_Utility_Permissions::get_user_roles();
		foreach ($user_roles as $id => $name) {
			if (!isset($data['aios_allowed_roles_rest_requests_'.$id])) {
				$aios_roles_disallowed_rest_requests[] = $id;
			}
		}
		$options['aios_roles_disallowed_rest_requests'] = $aios_roles_disallowed_rest_requests;

		// Commit the config settings
		$this->save_settings($options);

		$block_request_methods = array_map('strtolower', AIOS_Abstracted_Ids::get_firewall_block_request_methods());
		$methods = $aiowps_firewall_config->get_value('aiowps_6g_block_request_methods');
		if (empty($methods)) {
			$methods = array();
		}

		$blocked_query     = (bool) $aiowps_firewall_config->get_value('aiowps_6g_block_query');
		$blocked_request   = (bool) $aiowps_firewall_config->get_value('aiowps_6g_block_request');
		$blocked_referrers = (bool) $aiowps_firewall_config->get_value('aiowps_6g_block_referrers');
		$blocked_agents    = (bool) $aiowps_firewall_config->get_value('aiowps_6g_block_agents');
		$content = array('aios-6g-firewall-settings-container .aios-advanced-options-panel' => $aio_wp_security->include_template('wp-admin/firewall/partials/advanced-settings-6g.php', true, compact('methods', 'blocked_query', 'blocked_request', 'blocked_referrers', 'blocked_agents', 'block_request_methods')));

		$features = array(
			'firewall-pingback-rules',
			'firewall-disable-rss-and-atom',
			'firewall-forbid-proxy-comments',
			'firewall-deny-bad-queries',
			'firewall-advanced-character-string-filter',
			'firewall-enable-6g',
			'firewall-block-fake-googlebots',
			'firewall-ban-post-blank-headers',
			'disallow-unauthorised-requests',
		);

		$args = array(
			'badges' => $features,
			'content' => $content,
			'info' => $info,
			'extra_args' => array('xmlprc_warning' => $enable_pingback ? $aio_wp_security->include_template('wp-admin/firewall/partials/xmlrpc-warning-notice.php', true) : '')
		);

		return $this->handle_response(true, '', $args);
	}

	/**
	 * Perform saving .htaccess firewall settings
	 *
	 * @param array $data - the request data contains the firewall settings
	 *
	 * @return array - containing a status and message
	 */
	public function perform_htaccess_firewall_settings($data) {
		global $aio_wp_security;


		$options = array();
		$info = array();
		$message = '';
		$success = true;

		// Max file upload size in basic rules
		$upload_size = absint($data['aiowps_max_file_upload_size']);

		$max_allowed = apply_filters('aiowps_max_allowed_upload_config', 250); // Set a filterable limit of 250MB
		$max_allowed = absint($max_allowed);

		if ($upload_size > $max_allowed) {
			$upload_size = $max_allowed;
		} elseif (empty($upload_size) || 0 > $upload_size) {
			$upload_size = AIOS_FIREWALL_MAX_FILE_UPLOAD_LIMIT_MB;
			$info[] = __('Max file upload limit was set to default value, because you entered a negative or zero value');
		}

		// Store the current value in case the .htaccess write operation fails and we need to revert it
		$original_options = array(
			'aiowps_enable_basic_firewall' => $aio_wp_security->configs->get_value("aiowps_enable_basic_firewall"),
			'aiowps_max_file_upload_size' => $aio_wp_security->configs->get_value('aiowps_max_file_upload_size'),
			'aiowps_block_debug_log_file_access' => $aio_wp_security->configs->get_value("aiowps_block_debug_log_file_access"),
			'aiowps_disable_index_views' => $aio_wp_security->configs->get_value('aiowps_disable_index_views'),
		);


		// Save settings
		$options['aiowps_enable_basic_firewall'] = isset($data["aiowps_enable_basic_firewall"]) ? '1' : '';
		$options['aiowps_max_file_upload_size'] = $upload_size;
		$options['aiowps_block_debug_log_file_access'] = isset($data["aiowps_block_debug_log_file_access"]) ? '1' : '';
		$options['aiowps_disable_index_views'] = isset($data['aiowps_disable_index_views']) ? '1' : '';

		// Commit the config settings
		$this->save_settings($options);

		//Now let's write the applicable rules to the .htaccess file
		$res = AIOWPSecurity_Utility_Htaccess::write_to_htaccess();

		if (!$res) {
			$success = false;
			$message = __('Could not write to the .htaccess file', 'all-in-one-wp-security-and-firewall');

			$this->save_settings($original_options);
		}

		$features = array(
			'firewall-basic-rules',
			'firewall-block-debug-file-access',
			'firewall-disable-index-views',
		);

		$values = array('aiowps_max_file_upload_size' => $upload_size);

		$args = array(
			'badges' => $features,
			'info' => $info,
			'values' => $values
		);

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * Save and update the 5G firewall settings, and conditionally update the .htaccess file if needed.
	 *
	 * This function handles the saving of the 5G firewall settings based on user input. It checks if
	 * the 5G firewall setting has been modified and writes the applicable rules to the .htaccess file
	 * if necessary. In case of failure to write to the .htaccess file, it returns an error message.
	 *
	 * @param array $data The data array containing the 5G firewall setting.
	 *
	 * @global object $aio_wp_security The global instance of the All-In-One WP Security & Firewall plugin.
	 *
	 * @return array An array containing the status ('success' or 'error') and a message indicating
	 *               the result of the operation.
	 */
	public function perform_save_5g_settings($data) {
		global $aio_wp_security;

		$response = array(
			'status' => 'success',
			'message' => __('The settings were successfully updated.', 'all-in-one-wp-security-and-firewall')
		);

		$options = array();

		// If the user has changed the 5G firewall checkbox settings, then there is a need to write htaccess rules again.
		$is_5G_firewall_option_changed = ((isset($data['aiowps_enable_5g_firewall']) && '1' != $aio_wp_security->configs->get_value('aiowps_enable_5g_firewall')) || (!isset($data['aiowps_enable_5g_firewall']) && '1' == $aio_wp_security->configs->get_value('aiowps_enable_5g_firewall')));

		// Save settings
		$options['aiowps_enable_5g_firewall'] = isset($data['aiowps_enable_5g_firewall']) ? '1' : '';
		$this->save_settings($options);

		$res = true;

		if ($is_5G_firewall_option_changed) {
			$res = AIOWPSecurity_Utility_Htaccess::write_to_htaccess(); // let's write the applicable rules to the .htaccess file
		}

		if (!$res) {
			$response['status'] = 'error';
			$response['message'] = __('Could not write to the .htaccess file for the 5G firewall settings, please check the file permissions.', 'all-in-one-wp-security-and-firewall');
			// revert settings
			$options['aiowps_enable_5g_firewall'] = '';
			$this->save_settings($options);
		}

		return $response;
	}

	/**
	 * Perform saving blacklist settings
	 *
	 * @param array $data - the request data contains blacklist settings
	 *
	 * @return array - containing a status, message and feature badge html
	 */
	public function perform_save_blacklist_settings($data) {
		global $aio_wp_security;
		$aiowps_firewall_config = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::CONFIG);

		$options = array();
		$message = '';
		$success = true;

		$result = 1;
		$aiowps_enable_blacklisting = isset($data["aiowps_enable_blacklisting"]) ? '1' : '';
		$aiowps_firewall_config->set_value('aiowps_enable_blacklisting', $aiowps_enable_blacklisting);

		if (!empty($data['aiowps_banned_ip_addresses'])) {
			$ip_addresses = sanitize_textarea_field(stripslashes($data['aiowps_banned_ip_addresses']));
			$ip_list_array = AIOWPSecurity_Utility_IP::create_ip_list_array_from_string_with_newline($ip_addresses);
			$validated_ip_list_array = AIOWPSecurity_Utility_IP::validate_ip_list($ip_list_array, 'blacklist');
			if (is_wp_error($validated_ip_list_array)) {
				$result = -1;
				$success = false;
				$message = nl2br($validated_ip_list_array->get_error_message());
			} else {
				$banned_ip_addresses_list = preg_split('/\R/', $aio_wp_security->configs->get_value('aiowps_banned_ip_addresses')); // Historical settings where the separator may have depended on PHP_EOL.
				if ($banned_ip_addresses_list !== $validated_ip_list_array) {
					$banned_ip_data = implode("\n", $validated_ip_list_array);
					$options['aiowps_banned_ip_addresses'] = $banned_ip_data;
					$aiowps_firewall_config->set_value('aiowps_blacklist_ips', $validated_ip_list_array);
				}
				$data['aiowps_banned_ip_addresses'] = ''; // Clear the post variable for the banned address list.
			}
		} else {
			$options['aiowps_banned_ip_addresses'] = ''; // Clear the IP address config value
			$aiowps_firewall_config->set_value('aiowps_blacklist_ips', array());
		}

		if (!empty($data['aiowps_banned_user_agents'])) {
			$this->validate_user_agent_list(stripslashes($data['aiowps_banned_user_agents']));
		} else {
			// Clear the user agent list
			$options['aiowps_banned_user_agents'] = '';
			$aiowps_firewall_config->set_value('aiowps_blacklist_user_agents', array());
		}

		if (1 == $result) {
			$aio_wp_security->configs->set_value('aiowps_enable_blacklisting', $aiowps_enable_blacklisting, true);
			if ('1' == $aio_wp_security->configs->get_value('aiowps_is_ip_blacklist_settings_notice_on_upgrade')) {
				$aio_wp_security->configs->delete_value('aiowps_is_ip_blacklist_settings_notice_on_upgrade');
			}
		}

		$this->save_settings($options);

		$args = array(
			'badges' => array("blacklist-manager-ip-user-agent-blacklisting")
		);

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * The AJAX function for storing ips in firewall allowlist
	 *
	 * @param array $data - the request data contains data to updated
	 *
	 * @return array - containing a status and message
	 */
	public function perform_firewall_allowlist($data) {
		$aiowps_firewall_allow_list = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::ALLOW_LIST);

		$message = '';
		$success = true;
		$allowlist = $data['aios_firewall_allowlist'];

		if (empty($allowlist)) {
			$aiowps_firewall_allow_list::add_ips('');
			return $this->handle_response(true, '');
		}

		$ips = sanitize_textarea_field(wp_unslash($allowlist));
		$ips = AIOWPSecurity_Utility_IP::create_ip_list_array_from_string_with_newline($ips);
		$validated_ip_list_array = AIOWPSecurity_Utility_IP::validate_ip_list($ips, 'firewall_allowlist');

		if (is_wp_error($validated_ip_list_array)) {
			$success = false;
			$message = nl2br($validated_ip_list_array->get_error_message());
		} else {
			$aiowps_firewall_allow_list::add_ips($validated_ip_list_array);
		}

		return $this->handle_response($success, $message);
	}

	/**
	 * The AJAX function for saving PHP firewall and block and allowlists in UDC.
	 *
	 * @param array $data The data send from UDC.
	 *
	 * @return array|WP_Error
	 */
	public function perform_save_firewall($data) {
		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		$response = $this->perform_firewall_allowlist($data);
		if ('error' === $response['status']) {
			return $response;
		}

		$response = $this->perform_save_blacklist_settings($data);
		if ('error' === $response['status']) {
			return $response;
		}

		return $this->perform_php_firewall_settings($data);
	}

	/**
	 * Perform the setup process for the firewall.
	 *
	 * This function handles the setup form for the firewall and renders notices accordingly.
	 *
	 * @return array An array containing the content and message for the response.
	 */
	public function perform_setup_firewall() {
		global $aio_wp_security;

		$firewall_setup = AIOWPSecurity_Firewall_Setup_Notice::get_instance();
		$content = array('aiowps-firewall-status-container' => $aio_wp_security->include_template('wp-admin/firewall/partials/firewall-set-up-button.php', true));

		$firewall_setup->do_setup();
		ob_start();
		$firewall_setup->render_notices();
		$result = ob_get_clean();


		$args = array(
			'content' => $content,
			'extra_args' => array('info_box' => $result)
		);

		$message = false;

		if (AIOWPSecurity_Utility_Firewall::is_firewall_setup()) {
			$content['aiowps-firewall-status-container'] = $aio_wp_security->include_template('wp-admin/firewall/partials/firewall-downgrade-button.php', true);
			$message = __('Firewall has been setup successfully.', 'all-in-one-wp-security-and-firewall');
			$args['content'] = $content;
		}

		return $this->handle_response(AIOWPSecurity_Utility_Firewall::is_firewall_setup(), $message, $args);
	}

	/**
	 * Perform the downgrade process for the firewall.
	 *
	 * This function removes the firewall and returns a response indicating success.
	 *
	 * @return array An array containing the status, content, and message for the response.
	 */
	public function perform_downgrade_firewall() {
		global $aio_wp_security;

		AIOWPSecurity_Utility_Firewall::remove_firewall();

		$message = AIOWPSecurity_Utility_Firewall::is_firewall_setup() ? __('Something went wrong please try again later.', 'all-in-one-wp-security-and-firewall') : __('Firewall has been downgraded successfully.', 'all-in-one-wp-security-and-firewall');
		$success = true;
		$downgrade_button = $aio_wp_security->include_template('wp-admin/firewall/partials/firewall-set-up-button.php', true);
		$extra_args = array();

		if (AIOWPSecurity_Utility_Firewall::is_firewall_setup()) {
			$success = false;
			$downgrade_button = $aio_wp_security->include_template('wp-admin/firewall/partials/firewall-downgrade-button.php', true);
		} else {
			$extra_args['info_box'] = $aio_wp_security->include_template('notices/firewall-setup-notice.php', true, array('show_dismiss' => false));
		}

		$args = array(
			'content' => array('aiowps-firewall-status-container' => $downgrade_button),
			'extra_args' => $extra_args
		);

		return $this->handle_response($success, $message, $args);
	}

	/**
	 * Validates posted user agent list and set, save as config.
	 *
	 * @param string $banned_user_agents - List of banned user agents
	 *
	 * @return void
	 */
	private function validate_user_agent_list($banned_user_agents) {
		$aiowps_firewall_config = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::CONFIG);
		$submitted_agents = AIOWPSecurity_Utility::splitby_newline_trim_filter_empty($banned_user_agents);
		$agents = array_unique(
			array_filter(
				array_map(
					'sanitize_text_field',
					$submitted_agents
				),
				'strlen'
			)
		);
		$aiowps_firewall_config->set_value('aiowps_blacklist_user_agents', $agents);
		$this->save_settings(array(
			'aiowps_banned_user_agents' => implode("\n", $agents)
		));
	}

	/**
	 * This function performs save upgrade unsafe http calls settings.
	 *
	 * @param array $data - The request data.
	 *
	 * @return array
	 */
	public function perform_save_upgrade_unsafe_http_calls_settings($data) {
		$upgrade_unsafe_http_calls_url_exceptions = sanitize_textarea_field(wp_unslash($data['aiowps_upgrade_unsafe_http_calls_url_exceptions']));

		$errors = '';

		if (!empty($upgrade_unsafe_http_calls_url_exceptions)) {
			foreach (preg_split('/\R/', $upgrade_unsafe_http_calls_url_exceptions) as $url) {
				$url = esc_url_raw($url);

				if (empty($url)) {
					continue;
				}

				if (0 === strpos($url, '#')) {
					continue;
				}

				$parsed_url = parse_url($url); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Using the same function as WordPress in order to not preclude URLs that would be allowed by WordPress.

				if (empty($parsed_url['scheme'])) { // The same weak sanity check used by the WordPress wp_remote_* functions.
					/* translators: %s URL entered by user. */
					$errors .= "\n" . sprintf(__('%s is not a valid url.', 'all-in-one-wp-security-and-firewall'), $url);
					continue;
				}
			}
		}

		if (!empty($errors)) {
			return $this->handle_response(false, nl2br(trim($errors)), array('badges' => array('upgrade-unsafe-http-calls')));
		}

		$this->save_settings(array(
			'aiowps_upgrade_unsafe_http_calls' => isset($data['aiowps_upgrade_unsafe_http_calls']) ? '1' : '',
			'aiowps_upgrade_unsafe_http_calls_url_exceptions' => $upgrade_unsafe_http_calls_url_exceptions
		));

		return $this->handle_response(true, '', array('badges' => array('upgrade-unsafe-http-calls')));
	}

	/**
	 * Render the PHP firewall rules for the legacy UDC theme.
	 *
	 * @return array
	 */
	public function get_php_firewall_contents() {
		global $aio_wp_security;

		$GLOBALS['aiowps_feature_mgr'] = $this->get_feature_mgr_object();
		$php_firewall_data = $this->get_php_firewall_data();

		$content = $aio_wp_security->include_template('/wp-admin/firewall/php-firewall-rules.php', true, compact('php_firewall_data'));

		return array(
			'status' => 'success',
			'content' => $php_firewall_data['no_firewall'] . $content,
		);
	}

	/**
	 * Render the .htaccess firewall rules for the legacy UDC theme.
	 *
	 * @return array
	 */
	public function get_htaccess_contents() {
		global $aio_wp_security;

		$GLOBALS['aiowps_feature_mgr'] = $this->get_feature_mgr_object();

		$htaccess_rules_data = $this->get_htaccess_rules_data();

		$content = $aio_wp_security->include_template('/wp-admin/firewall/htaccess-firewall-rules.php', true, compact('htaccess_rules_data'));

		return array(
			'status' => 'success',
			'content' => $content,
		);
	}

	/**
	 * Render the Block & Allow Lists for the legacy UDC theme.
	 *
	 * @return array
	 */
	public function get_block_allow_lists_contents() {
		global $aio_wp_security;

		/* Needed for submit_button() */
		require_once(ABSPATH . 'wp-admin/includes/template.php');

		$GLOBALS['aiowps_feature_mgr'] = $this->get_feature_mgr_object();

		$block_allowlist_data = $this->get_block_allow_lists_data();

		$content = $aio_wp_security->include_template('wp-admin/firewall/block-and-allow-lists.php', true, $block_allowlist_data);

		return array(
			'status' => 'success',
			'content' => $content,
		);
	}

	/**
	 * Render the Advanced Settings for the legacy UDC theme.
	 *
	 * @return array
	 */
	public function get_advanced_settings_contents() {
		global $aio_wp_security;

		$GLOBALS['aiowps_feature_mgr'] = $this->get_feature_mgr_object();

		$advanced_settings_data = $this->get_firewall_advanced_settings_data();

		$content = $aio_wp_security->include_template('wp-admin/firewall/advanced-settings.php', true, compact('advanced_settings_data'));

		return array(
			'status' => 'success',
			'content' => $content,
		);
	}

	/**
	 * Return data for the advanced firewall.
	 *
	 * @return array
	 */
	public function get_firewall_advanced_settings_data() {
		global $aio_wp_security;

		$aiowps_upgrade_unsafe_http_calls = $aio_wp_security->configs->get_value('aiowps_upgrade_unsafe_http_calls');
		$aiowps_upgrade_unsafe_http_calls_url_exceptions = $aio_wp_security->configs->get_value('aiowps_upgrade_unsafe_http_calls_url_exceptions');

		return array(
			'aiowps_upgrade_unsafe_http_calls' => $aiowps_upgrade_unsafe_http_calls,
			'aiowps_upgrade_unsafe_http_calls_url_exceptions' => $aiowps_upgrade_unsafe_http_calls_url_exceptions,
		);
	}


	/**
	 * Return data for the allow & block lists.
	 *
	 * @return array
	 */
	public function get_block_allow_lists_data() {
		global $aio_wp_security;

		$aiowps_enable_blacklisting = $aio_wp_security->configs->get_value('aiowps_enable_blacklisting');
		$aiowps_banned_ip_addresses = $aio_wp_security->configs->get_value('aiowps_banned_ip_addresses');
		$aiowps_banned_user_agents = $aio_wp_security->configs->get_value('aiowps_banned_user_agents');

		$aiowps_firewall_allow_list = AIOS_Firewall_Resource::request(AIOS_Firewall_Resource::ALLOW_LIST);
		$allowlist = $aiowps_firewall_allow_list::get_ips();

		return array(
			'aiowps_enable_blacklisting' => $aiowps_enable_blacklisting,
			'aiowps_banned_ip_addresses' => $aiowps_banned_ip_addresses,
			'aiowps_banned_user_agents' => $aiowps_banned_user_agents,
			'allowlist' => $allowlist,
		);
	}

	/**
	 * Return data for the .htaccess rules.
	 *
	 * @return array
	 */
	public function get_htaccess_rules_data() {
		global $aio_wp_security;

		$aiowps_enable_basic_firewall = $aio_wp_security->configs->get_value('aiowps_enable_basic_firewall');
		$aiowps_max_file_upload_size = $aio_wp_security->configs->get_value('aiowps_max_file_upload_size');
		$aiowps_block_debug_log_file_access = $aio_wp_security->configs->get_value('aiowps_block_debug_log_file_access');
		$aiowps_disable_index_views = $aio_wp_security->configs->get_value('aiowps_disable_index_views');

		return array(
			'aiowps_enable_basic_firewall' => $aiowps_enable_basic_firewall,
			'aiowps_max_file_upload_size' => $aiowps_max_file_upload_size,
			'aiowps_block_debug_log_file_access' => $aiowps_block_debug_log_file_access,
			'aiowps_disable_index_views' => $aiowps_disable_index_views,
		);
	}

	/**
	 * Return data for the PHP firewall.
	 *
	 * @return array
	 */
	public function get_php_firewall_data() {
		global $aio_wp_security, $aiowps_firewall_config, $aiowps_feature_mgr;

		$is_udc_request = AIOS_Helper::is_updraft_central_request();

		$block_request_methods = array_map('strtolower', AIOS_Abstracted_Ids::get_firewall_block_request_methods());

		$no_firewall_notice = '';
		$user_roles = array();

		// Load required data from config
		if (!empty($aiowps_firewall_config)) {
			// firewall config is available
			$methods = $aiowps_firewall_config->get_value('aiowps_6g_block_request_methods');
			if (empty($methods)) {
				$methods = array();
			}

			$blocked_query     = (bool) $aiowps_firewall_config->get_value('aiowps_6g_block_query');
			$blocked_request   = (bool) $aiowps_firewall_config->get_value('aiowps_6g_block_request');
			$blocked_referrers = (bool) $aiowps_firewall_config->get_value('aiowps_6g_block_referrers');
			$blocked_agents    = (bool) $aiowps_firewall_config->get_value('aiowps_6g_block_agents');

			if (empty($methods) && (!$blocked_query && !$blocked_request && !$blocked_referrers && !$blocked_agents) && '1' == $aio_wp_security->configs->get_value('aiowps_enable_6g_firewall')) {
				$aio_wp_security->configs->set_value('aiowps_enable_6g_firewall', '');
				$aio_wp_security->configs->save_config();
				$aiowps_feature_mgr->check_feature_status_and_recalculate_points();
			}

		} else {
			if ($is_udc_request) {
				ob_start();
			}

			?>
			<div class="notice notice-error">
				<p><strong><?php esc_html_e('All-In-One Security', 'all-in-one-wp-security-and-firewall'); ?></strong></p>
				<p><?php esc_html_e('We were unable to access the firewall\'s configuration file:', 'all-in-one-wp-security-and-firewall');?></p>
				<pre style="max-width: 100%;background-color: #f0f0f0;border: #ccc solid 1px;padding: 10px;white-space: pre-wrap;"><?php echo esc_html(AIOWPSecurity_Utility_Firewall::get_firewall_rules_path() . 'settings.php'); ?></pre>
				<p><?php esc_html_e('As a result, the firewall will be unavailable.', 'all-in-one-wp-security-and-firewall');?></p>
				<p><?php esc_html_e('Please check your PHP error log for further information.', 'all-in-one-wp-security-and-firewall');?></p>
				<p><?php esc_html_e('If you\'re unable to locate your PHP log file, please contact your web hosting company to ask them where it can be found on their setup.', 'all-in-one-wp-security-and-firewall');?></p>
			</div>
			<?php

			if ($is_udc_request) {
				$no_firewall_notice .= ob_get_clean();
			}

			//set default variables
			$methods           = array();
			$blocked_query     = false;
			$blocked_request   = false;
			$blocked_referrers = false;
			$blocked_agents    = false;
		}

		$aiowps_enable_6g_firewall = $aio_wp_security->configs->get_value('aiowps_enable_6g_firewall');
		$advanced_options_disabled = '1' != $aiowps_enable_6g_firewall;

		$settings = array_merge(array('methods' => $methods), compact('aiowps_enable_6g_firewall', 'blocked_query', 'blocked_request', 'blocked_referrers', 'blocked_agents', 'block_request_methods', 'aiowps_firewall_config', 'advanced_options_disabled'));

		$aiowps_enable_pingback_firewall = $aiowps_firewall_config->get_value('aiowps_enable_pingback_firewall');
		$aiowps_disable_xmlrpc_pingback_methods = $aio_wp_security->configs->get_value('aiowps_disable_xmlrpc_pingback_methods');
		$aiowps_disable_rss_and_atom_feeds = $aio_wp_security->configs->get_value('aiowps_disable_rss_and_atom_feeds');
		$aiowps_forbid_proxy_comments = $aiowps_firewall_config->get_value('aiowps_forbid_proxy_comments');
		$aiowps_deny_bad_query_strings = $aiowps_firewall_config->get_value('aiowps_deny_bad_query_strings');
		$aiowps_advanced_char_string_filter = $aiowps_firewall_config->get_value('aiowps_advanced_char_string_filter');

		$aiowps_disallow_unauthorized_rest_requests = $aio_wp_security->configs->get_value('aiowps_disallow_unauthorized_rest_requests');
		$aios_roles_disallowed_rest_requests = $aio_wp_security->configs->get_value('aios_roles_disallowed_rest_requests');
		$aios_whitelisted_rest_routes = $aio_wp_security->configs->get_value('aios_whitelisted_rest_routes');
		$aiowps_block_fake_googlebots = $aiowps_firewall_config->get_value('aiowps_block_fake_googlebots');
		$aiowps_ban_post_blank_headers = $aiowps_firewall_config->get_value('aiowps_ban_post_blank_headers');

		$wp_user_roles = AIOWPSecurity_Utility_Permissions::get_user_roles();
		foreach ($wp_user_roles as $role => $role_name) {
			$user_roles[] = $role;
		}


		return array(
			'aiowps_enable_pingback_firewall' => $aiowps_enable_pingback_firewall,
			'aiowps_disable_xmlrpc_pingback_methods' => $aiowps_disable_xmlrpc_pingback_methods,
			'aiowps_disable_rss_and_atom_feeds' => $aiowps_disable_rss_and_atom_feeds,
			'aiowps_forbid_proxy_comments' => $aiowps_forbid_proxy_comments,
			'aiowps_deny_bad_query_strings' => $aiowps_deny_bad_query_strings,
			'aiowps_advanced_char_string_filter' => $aiowps_advanced_char_string_filter,
			'aiowps_disallow_unauthorized_rest_requests' => $aiowps_disallow_unauthorized_rest_requests,
			'aios_roles_disallowed_rest_requests' => $aios_roles_disallowed_rest_requests,
			'aios_whitelisted_rest_routes' => $aios_whitelisted_rest_routes,
			'user_roles' => $user_roles,
			'aiowps_block_fake_googlebots' => $aiowps_block_fake_googlebots,
			'aiowps_ban_post_blank_headers' => $aiowps_ban_post_blank_headers,
			'ng_settings' => $settings,
			'no_firewall' => $no_firewall_notice,
		);
	}
}
