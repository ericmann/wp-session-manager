<?php
/**
 * WordPress session managment.
 *
 * Standardizes WordPress session data and uses either database transients or in-memory caching
 * for storing user session information.
 *
 * @package WordPress
 * @subpackage Session
 * @since   3.6.0
 */

/**
 * Return the current cache expire setting.
 *
 * @return int
 */
function wp_session_cache_expire() {
	/** @var $wp_session WP_Session */
	global $wp_session;

	return $wp_session->cache_expiration();
}

function wp_session_cache_limiter() {
	// Todo: Implement this
}

/**
 * Alias of wp_session_write_close()
 */
function wp_session_commit() {
	wp_session_write_close();
}

function wp_session_decode() {
	// Todo: Implement this
}

function wp_session_destroy() {
	// Todo: Implement this
}

function wp_session_encode() {
	// Todo: Implement this
}

function wp_session_get_cookie_params() {
	// Todo: Implement this
}

function wp_session_id( $id = false ) {
	// Todo: Implement this
}

function wp_session_is_registered() {
	// Todo: Implement this
}

function wp_session_module_name() {
	// Todo: Implement this
}

function wp_session_name() {
	// Todo: Implement this
}

function wp_session_regenerate_id() {
	// Todo: Implement this
}

function wp_session_register_shutdown() {
	// Todo: Implement this
}

function wp_session_register() {
	// Todo: Implement this
}

function wp_session_save_path() {
	// Todo: Implement this
}

function wp_session_set_cookie_params() {
	// Todo: Implement this
}

function wp_session_set_save_handler() {
	// Todo: Implement this
}

/**
 * Start new or resume existing session.
 *
 * Resumes an existing session based on a value sent by the _wp_session cookie.
 *
 * @return bool
 */
function wp_session_start() {
	/** @var $wp_session WP_Session */
	global $wp_session;

	$wp_session = WP_Session::get_instance();
	do_action( 'wp_session_start' );

	return $wp_session->session_started();
}
add_action( 'plugins_loaded', 'wp_session_start' );

/**
 * Return the current session status.
 *
 * @return int
 */
function wp_session_status() {
	/** @var $wp_session WP_Session */
	global $wp_session;

	if ( $wp_session->session_started() ) {
		return PHP_SESSION_ACTIVE;
	}

	return PHP_SESSION_NONE;
}

function wp_session_unregister() {
	// Todo: Implement this
}

function wp_session_unset() {
	// Todo: Implement this
}

/**
 * Write session data and end session
 */
function wp_session_write_close() {
	/** @var $wp_session WP_Session */
	global $wp_session;

	$wp_session->write_data();
	do_action( 'wp_session_commit' );
}
add_action( 'shutdown', 'wp_session_write_close' );