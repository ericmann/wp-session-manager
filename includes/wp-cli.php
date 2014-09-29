<?php
/**
 * CLI commands for WP Session Manager
 *
 * @package    WP_Session
 * @subpackage Commands
 */
class WP_Session_Command extends WP_CLI_Command {

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

		WP_CLI::line( sprintf( '%d sessions currently exist.', absint( $sessions ) ) );
	}

	/**
	 * Delete sessions from the database.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Flag whether or not to purge all sessions from the database.
	 *
	 * ## EXAMPLES
	 *
	 *      wp session delete
	 *      wp session delete --all
	 *
	 * @synopsis [--all]
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function delete( $args, $assoc_args ) {

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

	}
}
WP_CLI::add_command( 'session', 'WP_Session_Command' );