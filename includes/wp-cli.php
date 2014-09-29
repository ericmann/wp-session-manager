<?php

/**
 * CLI commands for WP Session Manager
 *
 * @package    WP_Session
 * @subpackage Commands
 */
class WP_Session_Command extends \WP_CLI_Command {

	/**
	 * Count the total number of sessions stored in the database.
	 *
	 *
	 * ## EXAMPLES
	 *
	 *      wp session count
	 *
	 * @global wpdb $wpdb
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function count( $args, $assoc_args ) {
		$sessions = WP_Session_Utils::count_sessions();

		\WP_CLI::line( sprintf( '%d sessions currently exist.', absint( $sessions ) ) );
	}

	/**
	 * Delete sessions from the database.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Flag whether or not to purge all sessions from the database.
	 *
	 * [--batch=<batch>]
	 * : Set the batch size for deleting old sessions
	 *
	 * [--limit=<limit>]
	 * : Delete just this number of old sessions
	 *
	 * ## EXAMPLES
	 *
	 *      wp session delete
	 *      wp session delete [--batch=<batch>]
	 *      wp session delete [--limit=<limit>]
	 *      wp session delete [--all]
	 *
	 * @synopsis [--all] [--batch=<batch>] [--limit=<limit>]
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function delete( $args, $assoc_args ) {
		if ( isset( $assoc_args['limit'] ) ) {
			$limit = absint( $assoc_args['limit'] );

			$count = WP_Session_Utils::delete_old_sessions( $limit );

			if ( $count > 0 ) {
				\WP_CLI::line( sprintf( 'Deleted %d sessions.', $count ) );
			}

			// Clear memory
			self::free_up_memory();
			return;
		}

		// Determine if we're deleting all sessions or just a subset.
		$all = isset( $assoc_args['all'] );

		/**
		 * Determine the size of each batch for deletion.
		 *
		 * @param int
		 */
		$batch = isset( $assoc_args['batch'] ) ? absint( $assoc_args['batch'] ) : apply_filters( 'wp_session_delete_batch_size', 1000 );

		switch ( $all ) {
			case true:
				$count = WP_Session_Utils::delete_all_sessions();

				\WP_CLI::line( sprintf( 'Deleted all %d sessions.', $count ) );
				break;
			case false:
				do {
					$count = WP_Session_Utils::delete_old_sessions( $batch );

					if ( $count > 0 ) {
						\WP_CLI::line( sprintf( 'Deleted %d sessions.', $count ) );
					}

					// Clear memory
					self::free_up_memory();
				} while ( $count > 0 );
				break;
		}
	}

	/**
	 * Generate a number of dummy sessions for testing purposes.
	 *
	 * ## OPTIONS
	 *
	 * <count>
	 * : Number of sessions to create.
	 *
	 * [--expires=<date>]
	 * : Optional expiration time tagged for each session. Will use WordPress' local time.
	 *
	 * ## EXAMPLES
	 *
	 *      wp session generate 5000
	 *      wp session generate 5000 --expires="2014-11-09T08:00"
	 *
	 * @synopsis <count> [--expires=<date>]
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function generate( $args, $assoc_args ) {
		$count = absint( $args[0] );
		$date  = isset( $assoc_args['expires'] ) ? $assoc_args['expires'] : null;

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating sessions', $count );

		for ( $i = 0; $i < $count; $i ++ ) {
			WP_Session_Utils::create_dummy_session( $date );
			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Free up memory
	 *
	 * @global WP_Object_Cache $wp_object_cache
	 * @global wpdb            $wpdb
	 */
	private function free_up_memory() {
		global $wp_object_cache, $wpdb;
		$wpdb->queries = array();

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		$wp_object_cache->group_ops      = array();
		$wp_object_cache->stats          = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache          = array();
	}
}

\WP_CLI::add_command( 'session', 'WP_Session_Command' );