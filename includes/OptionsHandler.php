<?php
/**
 * WordPress Options-table Session Handler
 *
 * Like the original WP Session Handler, this class uses the WordPress
 * Options table for data storage.
 *
 * @package WP Session Manager
 * @subpackage Handlers
 * @since 3.0
 */

namespace EAMann\WPSession;

use EAMann\WPSession\Objects\Option;

/**
 * Use WordPress options (the legacy storage technique) to store data to avoid creating
 * custom data structures in the database.
 */
class OptionsHandler extends SessionHandler
{

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
     * @param string   $key  Session identifier.
     * @param string   $data Serialized session data.
     * @param callable $next Next write operation in the stack.
     *
     * @return mixed
     */
    public function write($key, $data, $next)
    {
        $item = new Option($data);
        $session_key = $this->sanitize($key);
        add_option("_wp_session_${session_key}", $item->data, '', 'no');
        add_option("_wp_session_expires_${session_key}", $item->time, '', 'no');

        return $next($key, $data);
    }

    /**
     * Grab the item from the database if it exists, otherwise delve deeper
     * into the stack and retrieve from another underlying middleware.
     *
     * @param string   $key  Session identifier.
     * @param callable $next Next read operation in the stack, might not be needed.
     *
     * @return string
     */
    public function read($key, $next)
    {
        $data = $this->directRead($key);
        if (false === $data) {
            // Passing the key unsanitized to the next handler to avoid weirdness.
            $data = $next($key);
            if (false !== $data) {
                $item = new Option($data);
                $session_key = $this->sanitize($key);
                add_option("_wp_session_${session_key}", $item->data, '', 'no');
                add_option("_wp_session_expires_${session_key}", $item->time, '', 'no');
            }
        }

        return $data;
    }

    /**
     * Get an item out of a WordPress option
     *
     * @param string $key Session identifier.
     *
     * @return bool|string
     */
    protected function directRead(string $key)
    {
        $session_key = $this->sanitize($key);

        $data = get_option("_wp_session_${session_key}");
        $expires = intval(get_option("_wp_session_expires_${session_key}"));
        if (false !== $data) {
            $item = new Option($data, $expires);
            if (!$item->isValid()) {
                $this->directDelete($session_key);

                return false;
            }

            return $item->data;
        }

        return false;
    }

    /**
     * Purge an item from the database immediately.
     *
     * @param string   $key  Session identifier.
     * @param callable $next Next delete operation in the stack.
     *
     * @return mixed
     */
    public function delete($key, $next)
    {
        $session_key = $this->sanitize($key);

        $this->directDelete($session_key);

        return $next($key);
    }

    /**
     * Delete a cached session value from the options table.
     *
     * @param string $key Session identifier.
     */
    protected function directDelete(string $key)
    {
        delete_option("_wp_session_${key}");
        delete_option("_wp_session_expires_${key}");
    }

    /**
     * Update the Options table by removing any items that are no longer valid.
     *
     * @param int      $maxlifetime Maximum number of seconds for which a session can live.
     * @param callable $next        Next clean operation in the stack.
     *
     * @global \wpdb $wpdb
     *
     * @return mixed
     */
    public function clean($maxlifetime, $next)
    {
        global $wpdb;

        // Session is expired if now - item.time > maxlifetime.
        // Said another way, if  item.time < now - maxlifetime.
        $filter = intval(time() - $maxlifetime);
        $keys = $wpdb->get_results(
            $wpdb->prepare(
                '
SELECT option_name, option_value FROM $wpdb->options 
WHERE option_name LIKE %s AND option_value > %d ORDER BY option_value LIMIT 0, 1000',
                'wp_session_expires_%',
                $filter
            )
        );

        foreach ($keys as $expiration) {
            $key = $expiration->option_name;
            $session_key = $this->sanitize(substr($key, 20));

            $this->directDelete($session_key);
        }

        return $next($maxlifetime);
    }

    /**
     * Remove all sessions from the options table, regardless of expiration.
     *
     * @global \wpdb $wpdb
     *
     * @return int Sessions deleted.
     */
    public static function deleteAll(): int
    {
        global $wpdb;

        $count = $wpdb->query(
            "DELETE FROM $wpdb->options WHERE option_name LIKE '_wp_session_%'"
        );

        return intval($count / 2);
    }
}
