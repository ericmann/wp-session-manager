<?php

/**
 * Utility class for sesion utilities
 *
 * THIS CLASS SHOULD NEVER BE INSTANTIATED
 */
class WP_Session_Utils {

	/**
	 * Remove all sessions from the database, regardless of expiration.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int Sessions deleted
	 */
	public static function delete_all_sessions() {
		global $wpdb;

        if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
            return \EAMann\WPSession\OptionsHandler::delete_all();
        } else {
            return $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sm_sessions" );
        }
	}

	/**
	 * Generate a new, random session ID.
	 *
	 * @return string
	 */
	public static function generate_id() {
		require_once( ABSPATH . 'wp-includes/class-phpass.php' );
		$hash = new PasswordHash( 8, false );

		return md5( $hash->get_random_bytes( 32 ) );
	}

	/**
	 * Get session from database.
	 *
	 * @param string $session_id The session ID to retrieve
	 * @param array  $default    The default value to return if the option does not exist.
	 *
	 * @return array Session data
	 */
	public static function get_session( $session_id, $default = array() ) {
		global $wpdb;

		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}sm_sessions WHERE session_key = %s",
				esc_sql($session_id)
			),
			ARRAY_A
		);

		if ( $session === NULL ) {
			return $default;
		}

		return unserialize($session['session_value']);
	}

    /**
     * Test whether or not a session exists
     *
     * @param string $session_id The session ID to retrieve
     *
     * @return bool
     */
	public static function session_exists( $session_id ) {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_sessions WHERE session_key = %s", $session_id));

        return $exists > 0;
    }
} 
