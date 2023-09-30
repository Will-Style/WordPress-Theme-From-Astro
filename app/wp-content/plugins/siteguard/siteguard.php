<?php
/*
Plugin Name: SiteGuard WP Plugin
Plugin URI: http://www.jp-secure.com/cont/products/siteguard_wp_plugin/index_en.html
Description: Only installing SiteGuard WP Plugin on WordPress, its security can be improved. SiteGurad WP Plugin is the plugin specialized for the protection against the attack to the management page and login. It also have the function to create the exclude rule for WAF (SiteGuard Server Edition, to use it, WAF should be installed on the Web server.)
Author: JP-Secure
Author URI: https://www.eg-secure.co.jp/
Text Domain: siteguard
Domain Path: /languages/
Version: 1.7.5
*/

/*
  Copyright 2014 EG Secure Solutions Inc (JP-Secure Inc)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = get_file_data( __FILE__, array( 'version' => 'Version' ) );
define( 'SITEGUARD_VERSION', $data['version'] );

define( 'SITEGUARD_PATH', plugin_dir_path( __FILE__ ) );
define( 'SITEGUARD_URL_PATH', plugin_dir_url( __FILE__ ) );

define( 'SITEGUARD_LOGIN_NOSELECT', 4 );
define( 'SITEGUARD_LOGIN_SUCCESS', 0 );
define( 'SITEGUARD_LOGIN_FAILED', 1 );
define( 'SITEGUARD_LOGIN_FAIL_ONCE', 2 );
define( 'SITEGUARD_LOGIN_LOCKED', 3 );

define( 'SITEGUARD_LOGIN_TYPE_NOSELECT', 2 );
define( 'SITEGUARD_LOGIN_TYPE_NORMAL', 0 );
define( 'SITEGUARD_LOGIN_TYPE_XMLRPC', 1 );

require_once 'classes/siteguard-base.php';
require_once 'classes/siteguard-config.php';
require_once 'classes/siteguard-htaccess.php';
require_once 'classes/siteguard-admin-filter.php';
require_once 'classes/siteguard-rename-login.php';
require_once 'classes/siteguard-login-history.php';
require_once 'classes/siteguard-login-lock.php';
require_once 'classes/siteguard-login-alert.php';
require_once 'classes/siteguard-captcha.php';
require_once 'classes/siteguard-disable-xmlrpc.php';
require_once 'classes/siteguard-disable-pingback.php';
require_once 'classes/siteguard-disable-author-query.php';
require_once 'classes/siteguard-waf-exclude-rule.php';
require_once 'classes/siteguard-updates-notify.php';
require_once 'admin/siteguard-menu-init.php';

global $siteguard_htaccess;
global $siteguard_config;
global $siteguard_admin_filter;
global $siteguard_rename_login;
global $siteguard_loginlock;
global $siteguard_loginalert;
global $siteguard_captcha;
global $siteguard_login_history;
global $siteguard_xmlrpc;
global $siteguard_pingback;
global $siteguard_author_query;
global $siteguard_waf_exclude_rule;
global $siteguard_updates_notify;

$siteguard_htaccess         = new SiteGuard_Htaccess();
$siteguard_config           = new SiteGuard_Config();
$siteguard_admin_filter     = new SiteGuard_AdminFilter();
$siteguard_rename_login     = new SiteGuard_RenameLogin();
$siteguard_loginlock        = new SiteGuard_LoginLock();
$siteguard_loginalert       = new SiteGuard_LoginAlert();
$siteguard_login_history    = new SiteGuard_LoginHistory();
$siteguard_captcha          = new SiteGuard_CAPTCHA();
$siteguard_xmlrpc           = new SiteGuard_Disable_XMLRPC();
$siteguard_pingback         = new SiteGuard_Disable_Pingback();
$siteguard_author_query     = new SiteGuard_Disable_Author_Query();
$siteguard_waf_exclude_rule = new SiteGuard_WAF_Exclude_Rule();
$siteguard_updates_notify   = new SiteGuard_UpdatesNotify();

function siteguard_activate() {
	global $siteguard_config, $siteguard_admin_filter, $siteguard_rename_login, $siteguard_login_history, $siteguard_captcha, $siteguard_loginlock, $siteguard_loginalert, $siteguard_xmlrpc, $siteguard_pingback, $siteguard_author_query, $siteguard_waf_exclude_rule, $siteguard_updates_notify;

	load_plugin_textdomain(
		'siteguard',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	$siteguard_config->set( 'show_admin_notices', '0' );
	$siteguard_config->update();
	$siteguard_admin_filter->init();
	$siteguard_rename_login->init();
	$siteguard_login_history->init();
	$siteguard_captcha->init();
	$siteguard_loginlock->init();
	$siteguard_loginalert->init();
	$siteguard_xmlrpc->init();
	$siteguard_pingback->init();
	$siteguard_author_query->init();
	$siteguard_waf_exclude_rule->init();
	$siteguard_updates_notify->init();
}
register_activation_hook( __FILE__, 'siteguard_activate' );

function siteguard_deactivate() {
	global $siteguard_config;
	$siteguard_config->set( 'show_admin_notices', '0' );
	$siteguard_config->update();
	SiteGuard_RenameLogin::feature_off();
	SiteGuard_AdminFilter::feature_off();
	SiteGuard_Disable_XMLRPC::feature_off();
	SiteGuard_WAF_Exclude_Rule::feature_off();
	SiteGuard_UpdatesNotify::feature_off();
}
register_deactivation_hook( __FILE__, 'siteguard_deactivate' );


class SiteGuard extends SiteGuard_Base {
	protected $menu_init;
	function __construct() {
		global $siteguard_config;
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		$this->htaccess_check();
		if ( is_admin() ) {
			include 'admin/siteguard-menu-login-history.php';
			$this->menu_init = new SiteGuard_Menu_Init();
			add_action( 'init', array( $this, 'set_cookie' ) );
			add_action( 'admin_init', array( $this, 'upgrade' ) );
			if ( '0' === $siteguard_config->get( 'show_admin_notices' ) && '1' === $siteguard_config->get( 'renamelogin_enable' ) ) {
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
				$siteguard_config->set( 'show_admin_notices', '1' );
				$siteguard_config->update();
			}
		}
	}
	function set_cookie() {
		SiteGuard_Menu_Login_History::set_cookie();
	}
	function plugins_loaded() {
		load_plugin_textdomain(
			'siteguard',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
	function htaccess_check() {
		global $siteguard_config;

		$can_use_htaccess = true;
		if ( false === SiteGuard_Htaccess::test_htaccess() ) {
			$can_use_htaccess = false;
		}
		if ( '1' === $siteguard_config->get( 'admin_filter_enable' ) ) {
			if ( false === $can_use_htaccess || ! SiteGuard_Htaccess::is_exists_setting( SiteGuard_AdminFilter::get_mark() ) ) {
				$siteguard_config->set( 'admin_filter_enable', '0' );
				$siteguard_config->update();
			}
		}
		if ( '1' === $siteguard_config->get( 'renamelogin_enable' ) ) {
			if ( false === $can_use_htaccess || ! SiteGuard_Htaccess::is_exists_setting( SiteGuard_RenameLogin::get_mark() ) ) {
				$siteguard_config->set( 'renamelogin_enable', '0' );
				$siteguard_config->update();
			}
		}
		if ( '1' === $siteguard_config->get( 'disable_xmlrpc_enable' ) ) {
			if ( false === $can_use_htaccess || ! SiteGuard_Htaccess::is_exists_setting( SiteGuard_Disable_XMLRPC::get_mark() ) ) {
				$siteguard_config->set( 'disable_xmlrpc_enable', '0' );
				$siteguard_config->update();
			}
		}
		if ( '1' === $siteguard_config->get( 'waf_exclude_rule_enable' ) ) {
			if ( false === $can_use_htaccess || ! SiteGuard_Htaccess::is_exists_setting( SiteGuard_WAF_Exclude_Rule::get_mark() ) ) {
				$siteguard_config->set( 'waf_exclude_rule_enable', '0' );
				$siteguard_config->update();
			}
		}
		if ( '1' === $siteguard_config->get( 'captcha_enable' ) ) {
			if ( false === $can_use_htaccess ) {
				$siteguard_config->set( 'captcha_enable', '0' );
				$siteguard_config->update();
			}
		}
	}
	function admin_notices() {
		global $siteguard_rename_login;
		echo '<div class="updated" style="background-color:#719f1d;"><p><span style="border: 4px solid #def1b8;padding: 4px 4px;color:#fff;font-weight:bold;background-color:#038bc3;">';
		echo esc_html__( 'Login page URL was changed.', 'siteguard' ) . '</span>';
		echo '<span style="color:#eee;">';
		echo esc_html__( ' Please bookmark ', 'siteguard' ) . '<a style="color:#fff;text-decoration:underline;" href="' . esc_url( wp_login_url() ) . '">';
		echo esc_html__( 'new login URL', 'siteguard' ) . '</a>';
		echo esc_html__( '. Setting change is ', 'siteguard' ) . '<a style="color:#fff;text-decoration:underline;" href="' . esc_url( menu_page_url( 'siteguard_rename_login', false ) ) . '">';
		echo esc_html__( 'here', 'siteguard' ) . '</a>';
		echo '.</span></p></div>';
		$siteguard_rename_login->send_notify();
	}
	function upgrade() {
		global $siteguard_config, $siteguard_rename_login, $siteguard_admin_filter, $siteguard_loginalert, $siteguard_updates_notify, $siteguard_login_history, $siteguard_xmlrpc, $siteguard_author_query;
		$upgrade_ok  = true;
		$old_version = $siteguard_config->get( 'version' );
		if ( '' === $old_version ) {
			$old_version = '0.0.0';
		}
		if ( $old_version === SITEGUARD_VERSION ) {
			return;
		}
		if ( version_compare( $old_version, '1.0.6' ) < 0 ) {
			if ( '1' === $siteguard_config->get( 'admin_filter_enable' ) ) {
				if ( true !== $siteguard_admin_filter->feature_on( $this->get_ip() ) ) {
					siteguard_error_log( 'Failed to update at admin_filter from ' . $old_version . ' to ' . SITEGUARD_VERSION . '.' );
					$upgrade_ok = false;
				}
			}
		}
		if ( version_compare( $old_version, '1.1.1' ) < 0 ) {
			$siteguard_loginalert->init();
		}
		if ( version_compare( $old_version, '1.2.0' ) < 0 ) {
			$siteguard_updates_notify->init();
		}
		if ( version_compare( $old_version, '1.2.5' ) < 0 ) {
			if ( '1' === $siteguard_config->get( 'admin_filter_enable' ) ) {
				$siteguard_admin_filter->cvt_status_for_1_2_5( $this->get_ip() );
			}
			if ( '1' === $siteguard_config->get( 'renamelogin_enable' ) ) {
				if ( true !== $siteguard_rename_login->feature_on() ) {
					siteguard_error_log( 'Failed to update at rename_login from ' . $old_version . ' to ' . SITEGUARD_VERSION . '.' );
					$upgrade_ok = false;
				}
			}
		}
		if ( version_compare( $old_version, '1.3.0' ) < 0 ) {
			$siteguard_login_history->init();
			$siteguard_xmlrpc->init();
		}
		if ( version_compare( $old_version, '1.5.0' ) < 0 ) {
			$admin_filter_exclude_path = $siteguard_config->get( 'admin_filter_exclude_path' );
			if ( false === strpos( $admin_filter_exclude_path, 'site-health.php' ) ) {
				$admin_filter_exclude_path .= ', site-health.php';
				$siteguard_config->set( 'admin_filter_exclude_path', $admin_filter_exclude_path );
				$siteguard_config->update();
			}
		}
		if ( version_compare( $old_version, '1.5.1' ) < 0 ) {
			if ( '1' === $siteguard_config->get( 'admin_filter_enable' ) ) {
				if ( true !== $siteguard_admin_filter->feature_on( $this->get_ip() ) ) {
					siteguard_error_log( 'Failed to update at admin_filter from ' . $old_version . ' to ' . SITEGUARD_VERSION . '.' );
					$upgrade_ok = false;
				}
			}
			if ( '1' === $siteguard_config->get( 'disable_xmlrpc_enable' ) ) {
				if ( true !== $siteguard_xmlrpc->feature_on() ) {
					siteguard_error_log( 'Failed to update at disable_xmlrpc from ' . $old_version . ' to ' . SITEGUARD_VERSION . '.' );
					$upgrade_ok = false;
				}
			}
		}
		if ( version_compare( $old_version, '1.6.0' ) < 0 ) {
			$siteguard_author_query->init();
		}
		if ( version_compare( $old_version, '1.7.0' ) < 0 ) {
			if ( '1' === $siteguard_config->get( 'admin_filter_enable' ) ) {
				if ( true !== $siteguard_admin_filter->feature_on( $this->get_ip() ) ) {
					siteguard_error_log( 'Failed to update at admin_filter from ' . $old_version . ' to ' . SITEGUARD_VERSION . '.' );
					$upgrade_ok = false;
				}
			}
		}
		if ( $upgrade_ok && SITEGUARD_VERSION !== $old_version ) {
			$siteguard_config->set( 'version', SITEGUARD_VERSION );
			$siteguard_config->update();
		}
	}
}
$siteguard = new SiteGuard();
