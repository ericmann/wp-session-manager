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
class DatabaseHandler extends SessionHandler
{

    /**
     * Create the new table for housing session data if we're not still using
     * the legacy options mechanism. This code should be invoked before
     * instantiating the singleton session manager to ensure the table exists
     * before trying to use it.
     *
     * @see https://github.com/ericmann/wp-session-manager/issues/55
     *
     * @return bool|\WP_Error True if the table exists, False if using options, Error if failed.
     */
    public static function createTable()
    {
        if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
            return false;
        }

        $current_db_version = '0.2';
        $created_db_version = get_option('sm_session_db_version', '0.0');

        // If we're up-to-date, don't run the update
        if ($created_db_version === $current_db_version) {
            return true;
        }

        global $wpdb;

        $collate = '';
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        require_once ABSPATH . 'wp-admin/install-helper.php';

        switch ($created_db_version) {
            case '0.1':
                $dropped = maybe_drop_column(
                    "{$wpdb->prefix}sm_sessions",
                    'session_id',
                    "ALTER TABLE {$wpdb->prefix}sm_sessions DROP COLUMN session_id;"
                );

                if (! $dropped) {
                    return new \WP_Error('Unable to update session tables!');
                }

                update_option('sm_session_db_version', '0.2');
                break;
            case '0.0':
            default:
                $created = maybe_create_table(
                    "{$wpdb->prefix}sm_sessions",
                    "CREATE TABLE {$wpdb->prefix}sm_sessions (
                        session_key char(32) NOT NULL,
                        session_value LONGTEXT NOT NULL,
                        session_expiry BIGINT(20) UNSIGNED NOT NULL,
                        PRIMARY KEY  (session_key)
                    ) $collate;"
                );

                if (! $created) {
                    return new \WP_Error('Unable to create session tables!');
                }

                add_option('sm_session_db_version', '0.2', '', 'no');

                // Nuke any legacy sessions from the options table.
                OptionsHandler::deleteAll();
        }

        return true;
    }

    /**
     * Pass things through to the next middleware. This function is a no-op.
     *
     * @param string   $path Path where the storage lives.
     * @param string   $name Name of the session store to create.
     * @param callable $next Next create operation in the stack.
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
     * @param string   $key  Key of the data to store.
     * @param string   $data Actual data to store.
     * @param callable $next Next write operation in the stack.
     *
     * @return mixed
     */
    public function write($key, $data, $next)
    {
        $this->directWrite($key, $data);

        return $next($key, $data);
    }

    /**
     * Actually write the data to the WordPress database.
     *
     * @param string $key     Key of the session to write.
     * @param string $data    Serialized data to write.
     * @param int    $expires Timestamp (in seconds from now) when the session expires.
     *
     * @global \wpdb $wpdb
     *
     * @return bool|int false if the row could not be inserted or the number of affected rows (which will always be 1).
     */
    protected function directWrite(string $key, string $data, int $expires = null)
    {
        global $wpdb;

        if (null === $wpdb) {
            return false;
        }

        if (null === $expires) {
            $lifetime = (int) ini_get('session.gc_maxlifetime');
            $expires = time() + $lifetime;
        }

        $session = [
            'session_key'    => $this->sanitize($key),
            'session_value'  => $data,
            'session_expiry' => $expires,
        ];

        if (empty($data)) {
            return $this->directDelete($key);
        }

        return $wpdb->replace("{$wpdb->prefix}sm_sessions", $session);
    }

    /**
     * Grab the item from the database if it exists, otherwise delve deeper
     * into the stack and retrieve from another underlying middlware.
     *
     * @param string   $key  Key of the session to read.
     * @param callable $next Next read operation in the stack.
     *
     * @return string
     */
    public function read($key, $next)
    {
        $data = $this->directRead($key);
        if (false === $data) {
            $data = $next($key);
            if (false !== $data) {
                $this->directWrite($key, $data);
            }
        }

        return $data;
    }

    /**
     * Get an item out of a WordPress option
     *
     * @param string $key Key of the session to read.
     *
     * @global \wpdb $wpdb
     *
     * @return bool|string
     */
    protected function directRead(string $key)
    {
        global $wpdb;
        $session_key = $this->sanitize($key);

        if (null === $wpdb) {
            return false;
        }

        $session = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}sm_sessions WHERE session_key = %s",
                $session_key
            ),
            ARRAY_A
        );

        if (null === $session) {
            return false;
        }

        return $session['session_value'];
    }

    /**
     * Purge an item from the database immediately.
     *
     * @param string   $key  Key of the session to purge.
     * @param callable $next Next delete operation in the stack.
     *
     * @return mixed
     */
    public function delete($key, $next)
    {
        $this->directDelete($key);

        return $next($key);
    }

    /**
     * Delete a session from the database.
     *
     * @param string $key Session identifier.
     *
     * @global \wpdb $wpdb
     */
    protected function directDelete(string $key)
    {
        global $wpdb;

        $session_key = $this->sanitize($key);

        if (null !== $wpdb) {
            $wpdb->delete("{$wpdb->prefix}sm_sessions", ['session_key' => $session_key]);
        }
    }

    /**
     * Update the database by removing any sessions that are no longer valid.
     *
     * @param int $maxlifetime (Unused).
     * @param callable $next Next clean operation in the stack.
     *
     * @return mixed
     */
    public function clean($maxlifetime, $next)
    {
        self::directClean();

        return $next($maxlifetime);
    }

    /**
     * Update the database by removing any sessions that are no longer valid
     *
     * @global \wpdb $wpdb
     */
    public static function directClean()
    {
        global $wpdb;

        if (null !== $wpdb) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}sm_sessions WHERE session_expiry < %s LIMIT %d",
                    time(),
                    1000
                )
            );
        }
    }
}
