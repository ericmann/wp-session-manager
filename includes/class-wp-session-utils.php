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

		$query = "SELECT COUNT(*) FROM {$wpdb->prefix}sm_sessions";

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
		self::add_session($session_id, $expires);
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

		$limit = absint( $limit );
		$now = time();
		$expired = array();
		$count = 0;
		$keys = $wpdb->get_results( "SELECT session_id FROM {$wpdb->prefix}sm_sessions WHERE session_expiry < {$now} LIMIT 0, {$limit}" );

		if ( !empty($keys) ) {
			foreach ( $keys as $key ) {
				$expired[] = $key->session_id;
				$count += 1;
			}
		}

		// Delete expired sessions
		if ( ! empty( $expired ) ) {
		    $placeholders = array_fill( 0, count( $expired ), '%s' );
		    $format = implode( ', ', $placeholders );
		    $query = "DELETE FROM {$wpdb->prefix}sm_sessions WHERE session_id IN ($format)";

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

		$count = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}sm_sessions" );

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
	 */
	public static function get_session( $session_id, $default = false ) {

		global $wpdb;

		$session = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}sm_sessions WHERE session_key = %s",
				esc_sql($session_id)
			)
		);

		if ( $session === NULL ) {
			return $default;
		}

		return $session;
	}


	/**
	 * Add session in database.
	 *
	 */
	public static function add_session( $session_id, $expires = 0 ) {
		global $wpdb;

		if ( $session_id == '' || $expires == '' ) {
			return;
		}

		$result = $wpdb->insert(
			"{$wpdb->prefix}sm_sessions",
			array(
				'session_key' => esc_sql( $session_id ),
				'session_expiry' => absint( $expires )
			),
			array(
				'%s',
				'%d'
			)
		);

		if ( $result !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Delete session in database.
	 *
	 */
	public static function delete_session( $session_id ) {
		global $wpdb;

		if ( $session_id == '' ) {
			return;
		}

		$wpdb->delete( "{$wpdb->prefix}sm_sessions", array( 'session_key' => esc_sql( $session_id ) ), array( '%s' ) );
	}

	/**
	 * Update session in database.
	 *
	 */
	public static function update_session( $session_id, $expires ) {
		global $wpdb;

		if ( $session_id == '' || $expires == '' ) {
			return;
		}

		$wpdb->update(
			"{$wpdb->prefix}sm_sessions",
			array(
				'session_expiry' => absint($expires)
			),
			array( 'session_key' => $session_id ),
			array(
				'%d'
			),
			array( '%s' )
		);
	}
} 