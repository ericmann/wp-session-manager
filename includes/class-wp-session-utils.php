<?php

/**
 * Utility class for sesion utilities
 *
 * THIS CLASS SHOULD NEVER BE INSTANTIATED
 */
class WP_Session_Utils {
	/**
	 * Count the total sessions in the database.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int
	 */
	public static function count_sessions() {
		global $wpdb;

        if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
            $query = "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_wp_session_expires_%'";
        } else {
            $query = "SELECT COUNT(*) FROM {$wpdb->prefix}sm_sessions";
        }

		/**
		 * Filter the query in case tables are non-standard.
		 *
		 * @param string $query Database count query
		 */
		$query = apply_filters( 'wp_session_count_query', $query );

		$sessions = $wpdb->get_var( $query );

		return absint( $sessions );
	}

	/**
	 * Create a new, random session in the database.
	 *
	 * @param null|string $date
	 */
	public static function create_dummy_session( $date = null ) {
		// Generate our date
		if ( null !== $date ) {
			$time = strtotime( $date );

			if ( false === $time ) {
				$date = null;
			} else {
				$expires = date( 'U', strtotime( $date ) );
			}
		}

		// If null was passed, or if the string parsing failed, fall back on a default
		if ( null === $date ) {
			/**
			 * Filter the expiration of the session in the database
			 *
			 * @param int
			 */
			$expires = time() + (int) apply_filters( 'wp_session_expiration', 30 * 60 );
		}

		$session_id = self::generate_id();

		// Store the session
        if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
            add_option( "_wp_session_{$session_id}", array(), '', 'no' );
            add_option( "_wp_session_expires_{$session_id}", $expires, '', 'no' );
        } else {
            self::add_session(array(
                'session_key' => $session_id,
                'session_value' => array(),
                'session_expiry' => $expires
            ));
        }
	}

	/**
	 * Delete old sessions from the database.
	 *
	 * @param int $limit Maximum number of sessions to delete.
	 *
	 * @global wpdb $wpdb
	 *
	 * @return int Sessions deleted.
	 */
	public static function delete_old_sessions( $limit = 1000 ) {
		global $wpdb;

        if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
            return self::delete_old_sessions_from_options($limit);
        }

		$limit = absint( $limit );
		$now = time();
		
		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}sm_sessions WHERE session_expiry < %s LIMIT %d",
				$now,
				$limit
			)
		);

		return $count;
	}

    /**
     * Delete old sessions from the options table.
     *
     * @param int $limit Maximum number of sessions to delete.
     *
     * @global wpdb $wpdb
     *
     * @return int Sessions deleted.
     */
	protected static function delete_old_sessions_from_options( $limit = 1000 ) {
        global $wpdb;

        $limit = absint( $limit );

        $keys = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '_wp_session_expires_%' ORDER BY option_value ASC LIMIT 0, {$limit}" );

        $now = time();
        $expired = array();
        $count = 0;

        foreach( $keys as $expiration ) {
            $key = $expiration->option_name;
            $expires = $expiration->option_value;

            if ( $now > $expires ) {
                $session_id = preg_replace("/[^A-Za-z0-9_]/", '', substr( $key, 20 ) );

                $expired[] = $key;
                $expired[] = "_wp_session_{$session_id}";
                $count += 1;
            }
 		}

 		// Delete expired sessions
 		if ( ! empty( $expired ) ) {
            $placeholders = array_fill( 0, count( $expired ), '%s' );
            $format = implode( ', ', $placeholders );
            $query = "DELETE FROM $wpdb->options WHERE option_name IN ($format)";

            $prepared = $wpdb->prepare( $query, $expired );
            $wpdb->query( $prepared );
        }

 		return $count;
    }

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
            self::delete_old_sessions_from_options();
        } else {
            $count = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sm_sessions" );
        }

		return $count;
	}

    /**
     * Remove all sessions from the options table, regardless of expiration.
     *
     * @global wpdb $wpdb
     *
     * @return int Sessions deleted
     */
	public static function delete_all_sessions_from_options() {
        global $wpdb;

        $count = $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_wp_session_%'" );

        return (int) ( $count / 2 );
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


	/**
	 * Add session in database.
	 *
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 *                    This means that if you are using GET or POST data you may need to use stripslashes() to avoid slashes ending up in the database.
	 *
	 * @return bool|int false if the row could not be inserted or the number of affected rows (which will always be 1). 
	 */
	public static function add_session( $data = array() ) {
		global $wpdb;

		if ( empty( $data ) ) {
			return false;
		}

		$result = $wpdb->insert( "{$wpdb->prefix}sm_sessions", $data );

		return $result;
	}

	/**
	 * Delete session in database.
	 *
	 * @param int $session_id The session ID to update
	 *
	 * @return bool
	 */
	public static function delete_session( $session_id = '' ) {
		global $wpdb;

		if ( $session_id == '' ) {
			return false;
		}

		$wpdb->delete( "{$wpdb->prefix}sm_sessions", array( 'session_key' => $session_id ) );

		return true;
	}

	/**
	 * Update session in database.
	 *
	 * @param int $session_id The session ID to update
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 *                    This means that if you are using GET or POST data you may need to use stripslashes() to avoid slashes ending up in the database.
	 *
	 * @return bool|int the number of rows updated, or false if there is an error.
	 *                  Keep in mind that if the $data matches what is already in the database, no rows will be updated, so 0 will be returned.
	 *                  Because of this, you should probably check the return with false === $result
	 */
	public static function update_session( $session_id = '', $data = array() ) {
		global $wpdb;

		if ( $session_id == '' || empty( $data ) ) {
			return false;
		}

		$result = $wpdb->update( "{$wpdb->prefix}sm_sessions", $data, array( 'session_key' => $session_id ) );
		
		return $result;
	}
} 