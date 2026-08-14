<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

abstract class AIOWPSecurity_Base_Tasks {
	/**
	 * Runs intended various tasks
	 * Handles single and multi-site (NW activation) cases
	 *
	 * @global type $wpdb
	 */
	public static function run() {
		if (is_multisite()) {
			global $wpdb;
			// check if it is a network activation
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- PCP warning. Ignore.
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				static::run_for_a_site();
				restore_current_blog();
			}
		} else {
			static::run_for_a_site();
		}
		Updraft_Tasks_Activation::uninstall(AIO_WP_SECURITY_PLUGIN_SLUG);

	}

	/**
	 * Run uninstallation task for a single site.
	 *
	 * This method must be implemented in child classes.
	 * Since static abstract methods are not allowed in PHP, we enforce it at runtime.
	 *
	 * @throws Exception If not overridden in a child class.
	 * @return void
	 */
	protected static function run_for_a_site() {
		throw new Exception(
			sprintf('%s : Child classes must implement run_for_a_site() method.', get_called_class())
		);
	}
}
