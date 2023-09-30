<?php

class SiteGuard_Disable_Author_Query extends SiteGuard_Base {

	function __construct() {
		global $siteguard_config;
		if ( '1' == $siteguard_config->get( 'block_author_query_enable' ) ) {
			add_action( 'init', array( $this, 'handler_author_query' ) );
			if ( '1' == $siteguard_config->get( 'disable_restapi_enable' ) ) {
				add_filter( 'rest_pre_dispatch', array( $this, 'handler_deny_rest_api' ), 10, 3 );
			}
		}
	}
	function init() {
		global $siteguard_config;
		$siteguard_config->set( 'block_author_query_enable', '0' );
		$siteguard_config->set( 'disable_restapi_enable', '0' );
		$siteguard_config->set( 'disable_restapi_exclude', 'oembed,contact-form-7,akismet' );
		$siteguard_config->update();
	}
	function handler_author_query() {
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			if ( ! is_admin() && preg_match( '/[?&]author=[0-9]+/i', $_SERVER['REQUEST_URI'] ) ) {
				wp_safe_redirect( home_url() );
				exit;
			}
		}
	}
	function handler_deny_rest_api( $result, $wp_rest_server, $request ) {
		global $siteguard_config;
		$exclude_app = preg_split( '/,/', $siteguard_config->get( 'disable_restapi_exclude' ) );

		$route = $request->get_route();
		foreach ( $exclude_app as $app ) {
			if ( strpos( $route, "/$app/" ) === 0 ) {
				return $result;
			}
		}
		if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
			return $result;
		}
		return new WP_Error(
			'rest_disabled',
			__( 'The REST API on this site has been disabled.' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
