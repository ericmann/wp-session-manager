<?php
/**
 * WordPress Options-table Session Handler
 *
 * Like the original WP Session Handler, this class uses the WordPress
 * Options table for data storage.
 *
 * @package WP Session Manager
 * @subpackage Handlers
 * @since 2.0
 */
namespace EAMann\Sessionz\Handlers;

use EAMann\Sessionz\Handler;
use EAMann\Sessionz\Objects\Option;

/**
 * Use an associative array to store session data so we can cut down on
 * round trips to an external storage mechanism (or just leverage an in-
 * memory cache for read performance).
 */
class OptionsHandler implements Handler {

	/**
	 * Sanitize a potential Session ID so we aren't fetching broken data
	 * from the options table.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	private function sanitize( $id ) {
		return preg_replace( "/[^A-Za-z0-9_]/", '', $id );
	}

	/**
	 * Helper function to either add or update an existing option in the table.
	 *
	 * @param string $id
	 * @param mixed  $data
	 */
	private function add_or_update( $id, $data ) {
		if ( ! add_option( $id, $data, '', 'no' ) ) {
			update_option( $id, $data );
		}
	}

	/**
	 * Get an item out of a WordPress option
	 *
	 * @param string $id
	 *
	 * @return bool|string
	 */
	protected function _read( $id ) {
		$session_id = $this->sanitize( $id );

		$data = get_option( "_wp_session_$session_id" );
		$expires = intval( get_option( "_wp_session_expires_$session_id" ) );
		if ( false !== $data ) {
			/** @var Option $item */
			$item = new Option( $data, $expires );
			if ( ! $item->is_valid() ) {
				delete_option( "_wp_session_$session_id" );
				delete_option( "_wp_session_expires_$session_id" );
				return false;
			}

			return $session;
		}

		return false;
	}

	/**
	 * Purge an item from the database immediately.
	 *
	 * @param string   $id
	 * @param callable $next
	 *
	 * @return mixed
	 */
	public function delete( $id, $next ) {
		$session_id = $this->sanitize( $id );

		delete_option( "_wp_session_$session_id" );
		delete_option( "_wp_session_expires_$session_id" );
		return $next( $id );
	}

	/**
	 * Update the Options table by removing any items that are no longer valid.
	 *
	 * @param int      $maxlifetime
	 * @param callable $next
	 *
	 * @global \wpdb $wpdb
	 *
	 * @return mixed
	 */
	public function clean( $maxlifetime, $next ) {
		global $wpdb;

		// Session is expired if now - item.time > maxlifetime
		// Said another way, if  item.time < now - maxlifetime
		$filter = intval( time() - $maxlifetime );
		$keys = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '_wp_session_expires_%' AND option_value > $filter ORDER BY option_value LIMIT 0, 1000" );

		foreach( $keys as $expiration ) {
			$key = $expiration->option_name;
			$session_id = $this->sanitize( substr( $key, 20 ) );

			delete_option( "_wp_session_$session_id" );
			delete_option( "_wp_session_expires_$session_id" );
		}

		return $next( $maxlifetime );
	}

	/**
	 * Pass things through to the next middleware. This function is a no-op.
	 *
	 * @param string   $path
	 * @param string   $name
	 * @param callable $next
	 *
	 * @return mixed
	 */
	public function create( $path, $name, $next ) {
		return $next( $path, $name );
	}

	/**
	 * Grab the item from the database if it exists, otherwise delve deeper
	 * into the stack and retrieve from another underlying middlware.
	 *
	 * @param string $id
	 * @param callable $next
	 *
	 * @return string
	 */
	public function read( $id, $next ) {
		$data = $this->_read( $id );
		if ( false === $data ) {
			$data = $next( $id );
			if ( false !== $data ) {
				$item = new Option( $data );
				$session_id = $this->sanitize( $id );
				$this->add_or_update( "_wp_session_$session_id", $item->data );
				$this->add_or_update( "_wp_session_expires_$session_id", $item->time );
			}
		}

		return $data;
	}

	/**
	 * Store the item in the database and then pass the data, unchanged, down
	 * the middleware stack.
	 *
	 * @param string   $id
	 * @param string   $data
	 * @param callable $next
	 *
	 * @return mixed
	 */
	public function write( $id, $data, $next ) {
		$item = new Option( $data );
		$session_id = $this->sanitize( $id );
		$this->add_or_update( "_wp_session_$session_id", $item->data );
		$this->add_or_update( "_wp_session_expires_$session_id", $item->time );

		return $next( $id, $data );
	}
}