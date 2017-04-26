<?php
/**
 * Deprecated methods for WP Session Manager
 *
 * @since 2.0
 */

/**
 * Return the current cache expire setting.
 *
 * @deprecated 2.0 Please use native Session functionality
 *
 * @return int
 */
function wp_session_cache_expire() {
	_doing_it_wrong( 'wp_session_cache_expire', 'Please use native Session functionality: session_cache_expire', '2.0' );

	return session_cache_expire();
}

/**
 * Alias of wp_session_write_close()
 *
 * @deprecated 2.0 Please use native Session functionality
 */
function wp_session_commit() {
	_doing_it_wrong( 'wp_session_commit', 'Please use native Session functionality: session_write_close', '2.0' );

	session_write_close();
}

/**
 * Load a JSON-encoded string into the current session.
 *
 * @deprecated 2.0 Please use native Session functionality
 *
 * @param string $data
 *
 * @return bool
 */
function wp_session_decode( $data ) {
	_doing_it_wrong( 'wp_session_decode', 'Please use native Session functionality: session_decode', '2.0' );

	return session_decode( $data );
}

/**
 * Encode the current session's data as a JSON string.
 *
 * @deprecated 2.0 Please use native Session functionality
 *
 * @return string
 */
function wp_session_encode() {
	_doing_it_wrong( 'wp_session_encode', 'Please use native Session functionality: session_encode', '2.0' );

	return session_encode();
}

/**
 * Regenerate the session ID.
 *
 * @deprecated 2.0 Please use native Session functionality
 *
 * @param bool $delete_old_session
 *
 * @return bool
 */
function wp_session_regenerate_id( $delete_old_session = false ) {
	_doing_it_wrong( 'wp_session_regenerate_id', 'Please use native Session functionality: session_regenerate_id', '2.0' );

	return session_regenerate_id( $delete_old_session );
}

/**
 * Start new or resume existing session.
 *
 * Resumes an existing session based on a value sent by the _wp_session cookie.
 *
 * @deprecated 2.0 Please use native Session functionality
 *
 * @return bool
 */
function wp_session_start() {
	_doing_it_wrong( 'wp_session_start', 'Please use native Session functionality: session_start', '2.0' );

	do_action( 'wp_session_start' );

	return session_start();
}

/**
 * Return the current session status.
 *
 * @deprecated 2.0 Please use native Session functionality
 *
 * @return int
 */
function wp_session_status() {
	_doing_it_wrong( 'wp_session_status', 'Please use native Session functionality: session_status', '2.0' );

	return session_status();
}

/**
 * Unset all session variables.
 *
 * @deprecated 2.0 Please use native Session functionality
 */
function wp_session_unset() {
	_doing_it_wrong( 'wp_session_unset', 'Please use native Session functionality: session_unset', '2.0' );

	session_unset();
}

/**
 * Write session data and end session
 *
 * @deprecated 2.0 Please use native Session functionality
 */
function wp_session_write_close() {
	_doing_it_wrong( 'wp_session_write_close', 'Please use native Session functionality: session_write_close', '2.0' );

	session_write_close();
	do_action( 'wp_session_commit' );
}

/**
 * Clean up expired sessions by removing data and their expiration entries from
 * the WordPress options table.
 *
 * This method should never be called directly and should instead be triggered as part
 * of a scheduled task or cron job.
 *
 * @deprecated 2.0
 */
function wp_session_cleanup() {
	_doing_it_wrong( 'wp_session_cleanup', 'Sessions are cleaned up natively.', '2.0' );
}

/**
 * Register the garbage collector as a twice daily event.
 *
 * @deprecated 2.0
 */
function wp_session_register_garbage_collection() {
	_doing_it_wrong( 'wp_session_register_garbage_collection', 'Sessions are cleaned up natively.', '2.0' );
}
