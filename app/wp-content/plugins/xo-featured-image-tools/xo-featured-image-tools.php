<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * XO Featured Image Tools plugin for WordPress.
 *
 * @package xo-featured-image-tools
 * @author  ishitaka
 * @license GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       XO Featured Image Tools
 * Plugin URI:        https://xakuro.com/wordpress/
 * Description:       Automatically generates the featured image from the image of the post.
 * Author:            Xakuro
 * Author URI:        https://xakuro.com/
 * License:           GPLv2
 * Requires at least: 4.9
 * Requires PHP:      5.6
 * Version:           1.15.3
 * Text Domain:       xo-featured-image-tools
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'XO_FEATURED_IMAGE_TOOLS_VERSION', '1.15.3' );

require_once __DIR__ . '/admin.php';

/**
 * XO Featured Image Tools main class.
 */
class XO_Featured_Image_Tools {
	/**
	 * XO Featured Image Tools admin.
	 *
	 * @var XO_Featured_Image_Tools_Admin
	 */
	public $admin;

	/**
	 * Construction.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		load_plugin_textdomain( 'xo-featured-image-tools' );

		$admin = new XO_Featured_Image_Tools_Admin( $this );
	}

	/**
	 * Gets the default value of the option.
	 *
	 * @since 0.3.0
	 */
	public static function get_default_options() {
		return array(
			'list_posts'               => array( 'post', 'page' ),
			'auto_save_posts'          => array( 'post', 'page' ),
			'external_image'           => false,
			'exclude_small_image'      => false,
			'exclude_small_image_size' => 99,
			'exclude_filenames'        => array( '*.gif' ),
			'skip_draft'               => true,
			'shortcode_content'        => false,
			'pattern_content'          => false,
		);
	}

	/**
	 * Plugin activation.
	 *
	 * @since 0.3.0
	 */
	public static function activation() {
		$options = get_option( 'xo_featured_image_tools_options' );
		if ( false === $options ) {
			add_option( 'xo_featured_image_tools_options', self::get_default_options() );
		}
	}

	/**
	 * Plugin deactivation.
	 *
	 * @since 0.3.0
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				delete_option( 'xo_featured_image_tools_options' );
			}
			restore_current_blog();
		} else {
			delete_option( 'xo_featured_image_tools_options' );
		}
	}
}

global $xo_featured_image_tools;
$xo_featured_image_tools = new XO_Featured_Image_Tools();

register_activation_hook( __FILE__, 'XO_Featured_Image_Tools::activation' );
register_uninstall_hook( __FILE__, 'XO_Featured_Image_Tools::uninstall' );
