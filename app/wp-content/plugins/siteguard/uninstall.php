<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

function remove_directory( $dir ) {
	$files = array_diff( scandir( $dir ), array( '.' ,'..' ) );
	foreach ( $files as $file ) {
		if ( is_dir( "$dir/$file" ) ) {
			remove_directory( "$dir/$file" );
		} else {
			unlink( "$dir/$file" );
		}
	}
	return rmdir( $dir );
}

function delete_siteguard_plugin() {
	global $wpdb;

	delete_option( 'siteguard_config' );

	$table_name = $wpdb->prefix . 'siteguard_login';
	$wpdb->query( "DROP TABLE IF EXISTS $table_name;" );

	$table_name = $wpdb->prefix . 'siteguard_history';
	$wpdb->query( "DROP TABLE IF EXISTS $table_name;" );

	remove_directory( WP_CONTENT_DIR . '/siteguard' );
}

delete_siteguard_plugin();
