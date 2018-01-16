<?php
/**
 * WordPress Custom-table Session Handler
 *
 * This class uses a custom database table to store session data
 *
 * @package WP Session Manager
 * @subpackage Handlers
 * @since 3.0
 */
namespace EAMann\WPSession;

/**
 * Store the session in a custom database table for the best performance.
 */
class DatabaseHandler extends SessionHandler {

    /**
     * Create the new table for housing session data if we're not still using
     * the legacy options mechanism. This code should be invoked before
     * instantiating the singleton session manager to ensure the table exists
     * before trying to use it.
     *
     * @see https://github.com/ericmann/wp-session-manager/issues/55
     */
    public static function create_table()
    {
        if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
            return;
        }

        $current_db_version = '0.1';
        $created_db_version = get_option('sm_session_db_version', '0.0');

        if (version_compare($created_db_version, $current_db_version, '<')) {
            global $wpdb;

            $collate = '';
            if ($wpdb->has_cap('collation')) {
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

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($table);

            add_option('sm_session_db_version', '0.1', '', 'no');

            // Nuke any legacy sessions from the options table.
            OptionsHandler::delete_all();
        }
    }

    /**
     * Pass things through to the next middleware. This function is a no-op.
     *
     * @param string   $path Path where the storage lives
     * @param string   $name Name of the session store to create
     * @param callable $next Next create operation in the stack
     *
     * @return mixed
     */
    public function create($path, $name, $next)
    {
        return $next($path, $name);
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
    public function write($id, $data, $next)
    {
        $this->_write($id, $data);

        return $next($id, $data);
    }

    /**
     * Actually write the data to the WordPress database.
     *
     * @param string $id        ID of the session to write
     * @param string $data      Serialized data to write
     * @param int    [$expires] Timestamp (in seconds from now) when the session expires
     *
     * @global \wpdb $wpdb
     *
     * @return bool|int false if the row could not be inserted or the number of affected rows (which will always be 1).
     */
    protected function _write($id, $data, $expires = null)
    {
        global $wpdb;

        if ($expires === null) {
            $lifetime = (int) ini_get('session.gc_maxlifetime');
            $expires = time() + $lifetime;
        }

        $session = [
            'session_key'    => $this->sanitize($id),
            'session_value'  => $data,
            'session_expiry' => $expires
        ];

        if (empty($data)) {
            return $this->_delete($id);
        }

        return $wpdb->replace("{$wpdb->prefix}sm_sessions", $session);
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
    public function read($id, $next)
    {
        $data = $this->_read($id);
        if (false === $data) {
            $data = $next($id);
            if (false !== $data) {
                $this->_write($id, $data);
            }
        }

        return $data;
    }

    /**
     * Get an item out of a WordPress option
     *
     * @param string $id
     *
     * @global \wpdb $wpdb
     *
     * @return bool|string
     */
    protected function _read($id)
    {
        global $wpdb;
        $session_id = $this->sanitize($id);

        $session = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_sessions WHERE session_key = %s",
                esc_sql($session_id)
            ),
            ARRAY_A
        );

        if ($session === null) {
            return false;
        }

        return $session[ 'session_value' ];
    }

    /**
     * Purge an item from the database immediately.
     *
     * @param string   $id
     * @param callable $next
     *
     * @return mixed
     */
    public function delete($id, $next)
    {
        $this->_delete($id);

        return $next($id);
    }

    /**
     * Delete a session from the database.
     *
     * @param string $id Session identifier
     *
     * @global \wpdb $wpdb
     */
    protected function _delete($id)
    {
        global $wpdb;

        $session_id = $this->sanitize( $id );

        $wpdb->delete( "{$wpdb->prefix}sm_sessions", ['session_key' => $session_id]);
    }

    /**
     * Update the database by removing any sessions that are no longer valid.
     *
     * @param int      $maxlifetime (Unused)
     * @param callable $next        Next clean operation in the stack
     *
     * @global \wpdb $wpdb
     *
     * @return mixed
     */
    public function clean($maxlifetime, $next)
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}sm_sessions WHERE session_expiry < %s LIMIT %d",
                time(),
                1000
            )
        );

        return $next($maxlifetime);
    }
}