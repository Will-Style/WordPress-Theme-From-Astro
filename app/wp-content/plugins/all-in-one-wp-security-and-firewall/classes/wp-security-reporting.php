<?php
if (!defined('ABSPATH')) {
	exit; //Exit if accessed directly
}

class AIOWPSecurity_Reporting {

	/**
	 * Generate a report
	 *
	 * @param string $output_format   Output format
	 * @param array  $section_content The content of the sections
	 * @param string $section_title   The title of the section
	 *
	 * @return string Report content
	 */
	public static function generate_report_sections($output_format, $section_content = array(), $section_title = '') {
		$data = '';

		$sanitized_section_content = array();

		foreach ($section_content as $key => $value) {
			$sanitized_key = esc_html($key);
			$sanitized_value = esc_html($value);
			$sanitized_section_content[$sanitized_key] = $sanitized_value;
		}

		if ('text' === $output_format) {
			$data .= "\n --- $section_title --- \n\n";
			$data .= self::output_section_data($sanitized_section_content);
			$data .= "\n===================================\n";
		} elseif ('table' === $output_format) {
			$data .= '<div class="postbox">';
			$data .= '<h3 class="hndle"><label for="title">' . $section_title . '</label></h3>';
			$data .= '<div class="inside" id="' . strtolower(str_replace(' ', '-', $section_title)) . '-info">';
			$data .= apply_filters('aiowp_security_report_section_content', AIOWPSecurity_Utility_UI::format_data_as_table($sanitized_section_content));
			$data .= '</div>';
			$data .= '</div>';
		}

		$data = apply_filters('aiowp_security_generate_report_section_below', $data);
	
		return $data;
	}

	/**
	 * Output the section data
	 *
	 * @param array $section_data Section data to output
	 *
	 * @return string Section data
	 */
	private static function output_section_data($section_data = array()) {
		$output = '';
		foreach ($section_data as $key => $value) {
			$output .= "$key - $value\n";
		}
		return $output;
	}

	/**
	 * Send notifications (email for now, webhook in premium plugin)
	 *
	 * @param array  $data Notification data
	 * @param string $type Notification type (email, webhook)
	 *
	 * @return boolean
	 */
	public static function notification($data, $type = 'email') {
		switch ($type) {
			case 'email':
				return self::send_email($data);
			case 'webhook':
				return apply_filters('aiowps_webhook_notification', false, $data);
			default:
				return false;
		}
	}

	/**
	 * Send an email
	 *
	 * @param array $data Email parameters including 'to', 'subject', 'message', 'headers', and 'attachments'
	 *
	 * @return bool Whether the email was sent successfully
	 */
	private static function send_email($data) {
		global $aio_wp_security;

		$to = isset($data['to']) ? $data['to'] : '';
		$subject = isset($data['subject']) ? $data['subject'] : '';
		$message = isset($data['message']) ? $data['message'] : '';
		$headers = isset($data['headers']) ? $data['headers'] : array();
		$attachments = isset($data['attachments']) ? $data['attachments'] : array();

		if (!is_array($to)) {
			$to = array($to);
		}

		if (!$aio_wp_security->sender_obj->send_email($to, $subject, $message, $headers, $attachments)) {
			$aio_wp_security->debug_logger->log_debug('AIOWPSecurity_Reporting: Email sending failed to ' . implode(',', $to), 4);
			return false;
		}
		return true;
	}
}
