<?php
/**
 * Plugin Name: WP Session Manager
 * Plugin URI:  https://paypal.me/eam
 * Description: Prototype session management for WordPress.
 * Version:     3.0.0
 * Author:      Eric Mann
 * Author URI:  http://eamann.com
 * License:     GPLv2+
 */

require __DIR__ . '/vendor/autoload.php';

// Queue up the session stack
$handler_stack = EAMann\Sessionz\Manager::initialize()->addHandler( new \EAMann\Sessionz\Handlers\OptionsHandler() );

if ( defined( 'WP_SESSION_ENC_KEY' )&& WP_SESSION_ENC_KEY ) {
	$handler_stack = $handler_stack->addHandler( new \EAMann\Sessionz\Handlers\EncryptionHandler( WP_SESSION_ENC_KEY ) );
}

$handler_stack->addHandler( new \EAMann\Sessionz\Handlers\MemoryHandler() );

// Include WP_CLI routines early
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include 'includes/class-wp-session-utils.php';
	include 'includes/wp-cli.php';
}

// Only include the functionality if it's not pre-defined.
if ( ! class_exists( 'WP_Session' ) ) {
	include 'includes/class-wp-session.php';
	include 'includes/wp-session.php';
}

// Create the required table.
add_action('admin_init', 'create_sm_sessions_table');
add_action('wp_session_init', 'create_sm_sessions_table');

/**
 * Create the new table for housing session data if we're not still using
 * the legacy options mechanism. This code should be invoked before
 * instantiating the singleton session manager to ensure the table exists
 * before trying to use it.
 *
 * @see https://github.com/ericmann/wp-session-manager/issues/55
 */
function create_sm_sessions_table() {
    if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
        return;
    }

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
		  session_key char(32) NOT NULL,
		  session_value LONGTEXT NOT NULL,
		  session_expiry BIGINT(20) UNSIGNED NOT NULL,
		  PRIMARY KEY  (session_key),
		  UNIQUE KEY session_id (session_id)
		) $collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $table );

		add_option('sm_session_db_version', '0.1', '', 'no');

		WP_Session_Utils::delete_all_sessions_from_options();
	}
}

// Start up session management, if we're not in the CLI
if ( ! defined( 'WP_CLI' ) || false === WP_CLI ) {
	add_action( 'plugins_loaded', 'session_start' );
}
