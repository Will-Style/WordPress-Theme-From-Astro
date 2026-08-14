<?php
/**
 * Uninstall UpdraftPlus Plugin
 *
 * Executed when the plugin is uninstalled.
 * Removes all plugin-related options, schedules, and temporary data.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct $wpdb query is required for this operation.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- some query operations need to always receive the most up-to-date or actual data directly from the database, reducing the risk of serving stale information.
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

$deinstall_option = get_site_option('updraftplus_deinstall_option', 'no');

/**
 * Respect user preference (if set to "no", keep data)
 */
if ('no' === $deinstall_option) {
	return;
}

$class_updraftplus_file_path = plugin_dir_path(__FILE__).'class-updraftplus.php';

if (!is_file($class_updraftplus_file_path)) return;
	
require_once($class_updraftplus_file_path);

if (!function_exists('updraftplus_uninstall_clear_options_transients')) {
	/**
	 * Clear options and transients from the option table
	 */
	function updraftplus_uninstall_clear_options_transients() {
		global $udp_settings_keys, $wpdb;
		$options_list = implode(',', array_fill(0, count(array_merge($udp_settings_keys['options'], $udp_settings_keys['meta'])), '%s'));
		$sql = $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name IN ($options_list)", array_merge($udp_settings_keys['options'], $udp_settings_keys['meta'])); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic placeholders for IN clause are prepared; interpolation is required for variable number of keys.
		$wpdb->query($sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The query is prepared above with dynamic placeholders, so this direct query execution is intentional and safe.


		foreach ($udp_settings_keys['prefixed_options'] as $pattern) {
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern));
		}

		foreach ($udp_settings_keys['transients'] as $pattern) {
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s OR option_name = %s OR option_name = %s OR option_name = %s", '_transient_'.$pattern, '_site_transient_'.$pattern, '_transient_timeout_'.$pattern, '_site_transient_timeout_'.$pattern));
		}

		foreach ($udp_settings_keys['prefixed_transients'] as $pattern) {
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", '_transient_'.$pattern, '_site_transient_'.$pattern, '_transient_timeout_'.$pattern, '_site_transient_timeout_'.$pattern));
		}
	}
}

global $wpdb, $udp_settings_keys;

$udp_settings_keys = UpdraftPlus::get_system_identifiers_list();

updraftplus_uninstall_clear_options_transients();

/**
 * If multisite: delete site-wide (network) options and subsite-specific ones
 */
if (is_multisite()) {
	
	// Delete options stored in wp_sitemeta
	array_walk($udp_settings_keys['meta'], 'delete_site_option');

	// remove any data in the sitemeta table that has meta_key prefixed with "updraftplus_" (e.g. updraftplus_options, updraftplus_product_key, updraftplus_product_keymeta, etc.)
	foreach ($udp_settings_keys['prefixed_options'] as $pattern) {
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $pattern));
	}

	foreach ($udp_settings_keys['transients'] as $pattern) {
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE meta_key = %s OR meta_key = %s", '_site_transient_'.$pattern, '_site_transient_timeout_'.$pattern));
	}

	foreach ($udp_settings_keys['prefixed_transients'] as $pattern) {
		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s", '_site_transient_'.$pattern, '_site_transient_timeout_'.$pattern));
	}

	$main_blog_id = function_exists('get_main_site_id') ? get_main_site_id() : get_current_site()->blog_id;
	
	if (function_exists('get_sites')) {
		$subsites = get_sites(array('fields' => 'ids', 'site__not_in' => array($main_blog_id)));
	} else {
		$raw_sites = wp_get_sites(); // phpcs:ignore WordPress.WP.DeprecatedFunctions.wp_get_sitesFound -- wp_get_sites() is used as a fallback for WP < 4.6 legacy support only.
		$subsites = array();
		foreach ($raw_sites as $site) {
			if ((int) $site['blog_id'] !== (int) $main_blog_id) {
				$subsites[] = $site['blog_id'];
			}
		}
	}

	foreach ($subsites as $blog_id) {
		switch_to_blog($blog_id);

		updraftplus_uninstall_clear_options_transients();

		restore_current_blog();
	}
}

/**
 * Clear scheduled crons
 */

foreach ($udp_settings_keys['crons'] as $hook) {
	wp_clear_scheduled_hook($hook);
}

delete_site_option('updraftplus_deinstall_option');
