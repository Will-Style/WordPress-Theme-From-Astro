<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (trait_exists('AIOWPSecurity_Tfa_Commands_Trait')) return;

trait AIOWPSecurity_Tfa_Commands_Trait {

	/**
	 * Init TFA for UDC.
	 *
	 * @return AIO_WP_Security_Simba_Two_Factor_Authentication_Plugin
	 */
	private function init_tfa() {
		include_once AIO_WP_SECURITY_PATH . '/classes/wp-security-two-factor-login.php';

		$tfa = new AIO_WP_Security_Simba_Two_Factor_Authentication_Plugin();

		/* Needed to run hook-dependent code in TFA, or there are Divide by Zero errors. */
		do_action('plugins_loaded');

		return $tfa;
	}

	/**
	 * Saves the TFA algorithm setting.
	 *
	 * @param array $data Passed arguments.
	 *
	 * @return array|WP_Error
	 */
	public function save_algorithm_setting($data) {
		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		global $current_user;

		$tfa = $this->init_tfa();

		$controller = $tfa->get_controller();

		$old_algorithm = $controller->get_user_otp_algorithm($current_user->ID);

		if ($old_algorithm != $data['tfa_algorithm_type']) {
			$controller->changeUserAlgorithmTo($current_user->ID, $data['tfa_algorithm_type']);
		}

		return array(
			'status' => 'success',
		);
	}

	/**
	 * Saves the TFA activation setting.
	 *
	 * @param array $data Passed arguments.
	 *
	 * @return array|WP_Error
	 */
	public function save_activation_setting($data) {
		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		global $current_user;

		$tfa = $this->init_tfa();

		$tfa->change_tfa_enabled_status($current_user->ID, $data['tfa_enable_tfa']);

		return array(
			'status' => 'success',
		);
	}

	/**
	 * Updates the TFA private key.
	 *
	 * @return array|WP_Error
	 */
	public function update_private_key() {
		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		global $current_user;

		$user_id = $current_user->ID;

		delete_user_meta($user_id, 'tfa_priv_key_64');
		delete_user_meta($user_id, 'simba_tfa_emergency_codes_64');

		return array(
			'status' => 'success',
		);
	}

	/**
	 * Updates the TFA OTP Code.
	 *
	 * @param array $data Passed arguments.
	 *
	 * @return array|WP_Error
	 */
	public function update_otp_code($data) {
		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		global $current_user;

		$tfa = $this->init_tfa();

		if ('refreshotp' == $data['subaction']) {
			$code = $tfa->get_controller()->get_current_code($current_user->ID);

			if (false === $code) {
				return array(
					'status' => 'error',
					'code' => '',
				);
			}

			return array(
				'status' => 'success',
				'code' => $code,
			);
		} elseif ('untrust_device' == $data['subaction']) {
			global $current_user;

			$trusted_devices = $tfa->user_get_trusted_devices();

			$trusted_device = $trusted_devices[wp_unslash($data['device_id'])];

			if (isset($trusted_device)) {
				unset($trusted_device);
			}

			$current_user_id = $current_user->ID;

			$tfa->user_set_trusted_devices($current_user_id, $trusted_devices);

			$trusted_list = $tfa->include_template('trusted-devices-inner-box.php', array('trusted_devices' => $tfa->user_get_trusted_devices()), true);

			return array(
				'status' => 'success',
				'trusted_list' => $trusted_list,
			);
		}

		exit;
	}

	/**
	 * Renders the TFA UI.
	 *
	 * @return array
	 */
	public function get_tfa_contents() {
		if (!function_exists('submit_button')) {
			require_once(ABSPATH . 'wp-admin/includes/template.php');
		}

		$tfa = $this->init_tfa();

		$content = $tfa->include_template('user-settings.php', array('simba_tfa' => $tfa), true);

		return array(
			'status' => 'success',
			'content' => $content,
		);
	}

	/**
	 * Get the TFA settings data for the new UDC theme.
	 *
	 * @return array
	 */
	public function get_tfa_data() {
		$tfa = $this->init_tfa();

		return array(
			'tfa_required_administrator' => $tfa->get_option('tfa_required_administrator'),
			'tfa_administrator' => $tfa->get_option('tfa_administrator'),
		);
	}

	/**
	 * Save the TFA settings data for the new UDC theme.
	 *
	 * @param array $data The data to save.
	 *
	 * @return array|WP_Error
	 */
	public function perform_save_tfa($data) {
		if (!AIOWPSecurity_Utility_Permissions::has_manage_cap()) {
			return new WP_Error(esc_html__('Sorry, you do not have enough privilege to execute the requested action.', 'all-in-one-wp-security-and-firewall'));
		}

		$success = false;
		$message = '';

		$tfa = $this->init_tfa();

		$value = isset($data["tfa_required_administrator"]) ? '1' : '';

		if ($tfa->update_option('tfa_required_administrator', $value)) {
			$tfa->update_option('tfa_administrator', $value);

			$success = true;
		}

		return $this->handle_response($success, $message);
	}
}
