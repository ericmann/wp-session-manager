<?php
/**
 * Plugin Name: WP Session Manager
 * Plugin URI:  http://jumping-duck.com/wordpress/plugins
 * Description: Prototype session management for WordPress.
 * Version:     1.2.2
 * Author:      Eric Mann
 * Author URI:  http://eamann.com
 * License:     GPLv2+
 */

// let users change the session cookie name
if( ! defined( 'WP_SESSION_COOKIE' ) ) {
	define( 'WP_SESSION_COOKIE', '_wp_session' );
}

if ( ! class_exists( 'Recursive_ArrayAccess' ) ) {
	include 'includes/class-recursive-arrayaccess.php';
}

// Include utilities class
if ( ! class_exists( 'WP_Session_Utils' ) ) {
	include 'includes/class-wp-session-utils.php';
}

// Include WP_CLI routines early
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include 'includes/wp-cli.php';
}

// Only include the functionality if it's not pre-defined.
if ( ! class_exists( 'WP_Session' ) ) {
	include 'includes/class-wp-session.php';
	include 'includes/wp-session.php';
}

// Create the required table.
add_action('admin_init', 'create_sm_sessions_table' );
function create_sm_sessions_table() {
	$current_db_version = '0.1';
	$created_db_version = get_option('sm_session_db_version', '0.0' );

	if ( version_compare( $created_db_version, $current_db_version, '<' ) ) {
		global $wpdb;

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$table = "CREATE TABLE {$wpdb->prefix}sm_sessions (
		  session_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		  session_key char(32) NOT NULL COLLATE 'utf8mb4_unicode_ci',
		  session_expiry BIGINT(20) UNSIGNED NOT NULL,
		  PRIMARY KEY  (session_key),
		  UNIQUE KEY session_id (session_id)
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $table );
	}
}
