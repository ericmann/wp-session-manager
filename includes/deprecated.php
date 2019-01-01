<?php
/**
 * Deprecated methods for WP Session Manager
 *
 * @since 3.0
 */

/**
 * Return the current cache expire setting.
 *
 * @deprecated 3.0 Please use native Session functionality
 *
 * @return int
 */
function wp_session_cache_expire(): int
{
    _doing_it_wrong('wp_session_cache_expire', 'Please use native Session functionality: session_cache_expire', '3.0');

    return session_cache_expire();
}

/**
 * Alias of wp_session_write_close()
 *
 * @deprecated 3.0 Please use native Session functionality
 */
function wp_session_commit()
{
    _doing_it_wrong('wp_session_commit', 'Please use native Session functionality: session_write_close', '3.0');

    session_write_close();
}

/**
 * Load a JSON-encoded string into the current session.
 *
 * @deprecated 3.0 Please use native Session functionality
 *
 * @param string $data
 *
 * @return bool
 */
function wp_session_decode(string $data)
{
    _doing_it_wrong('wp_session_decode', 'Please use native Session functionality: session_decode', '3.0');

    return session_decode($data);
}

/**
 * Encode the current session's data as a JSON string.
 *
 * @deprecated 3.0 Please use native Session functionality
 *
 * @return string
 */
function wp_session_encode(): string
{
    _doing_it_wrong('wp_session_encode', 'Please use native Session functionality: session_encode', '3.0');

    return session_encode();
}

/**
 * Regenerate the session ID.
 *
 * @deprecated 3.0 Please use native Session functionality
 *
 * @param bool $delete_old_session
 *
 * @return bool
 */
function wp_session_regenerate_id(bool $delete_old_session = false): int
{
    _doing_it_wrong(
        'wp_session_regenerate_id',
        'Please use native Session functionality: session_regenerate_id',
        '3.0'
    );

    return session_regenerate_id($delete_old_session);
}

/**
 * Start new or resume existing session.
 *
 * Resumes an existing session based on a value sent by the _wp_session cookie.
 *
 * @deprecated 3.0 Please use native Session functionality
 *
 * @return bool
 */
function wp_session_start(): bool
{
    _doing_it_wrong('wp_session_start', 'Please use native Session functionality: session_start', '3.0');

    do_action('wp_session_start');

    return session_start();
}

/**
 * Return the current session status.
 *
 * @deprecated 3.0 Please use native Session functionality
 *
 * @return int
 */
function wp_session_status(): int
{
    _doing_it_wrong('wp_session_status', 'Please use native Session functionality: session_status', '3.0');

    return session_status();
}

/**
 * Unset all session variables.
 *
 * @deprecated 3.0 Please use native Session functionality
 */
function wp_session_unset()
{
    _doing_it_wrong('wp_session_unset', 'Please use native Session functionality: session_unset', '3.0');

    session_unset();
}

/**
 * Write session data and end session
 *
 * @deprecated 3.0 Please use native Session functionality
 */
function wp_session_write_close()
{
    _doing_it_wrong('wp_session_write_close', 'Please use native Session functionality: session_write_close', '3.0');

    session_write_close();
    do_action('wp_session_commit');
}

/**
 * Clean up expired sessions by removing data and their expiration entries from
 * the WordPress options table.
 *
 * This method should never be called directly and should instead be triggered as part
 * of a scheduled task or cron job.
 *
 * @deprecated 3.0
 */
function wp_session_cleanup()
{
    _doing_it_wrong('wp_session_cleanup', 'Sessions are cleaned up natively.', '3.0');
}

/**
 * Register the garbage collector as a twice daily event.
 *
 * @deprecated 3.0
 */
function wp_session_register_garbage_collection()
{
    _doing_it_wrong('wp_session_register_garbage_collection', 'Sessions are cleaned up natively.', '3.0');
}

// phpcs:disable
if (!class_exists('WP_Session')) :
    class WP_Session implements ArrayAccess
    {
        public static function get_instance(): WP_Session
        {
            _doing_it_wrong('WP_Session::get_instance', 'Please use native Session functionality.', '3.0');

            return new WP_Session();
        }

        public function offsetExists($offset)
        {
            _doing_it_wrong('WP_Session::get_instance', 'Please use native Session functionality.', '3.0');

            return isset($_SESSION[$offset]);
        }

        public function offsetGet($offset)
        {
            _doing_it_wrong('WP_Session::get_instance', 'Please use native Session functionality.', '3.0');

            return $_SESSION[$offset];
        }

        public function offsetSet($offset, $value)
        {
            _doing_it_wrong('WP_Session::get_instance', 'Please use native Session functionality.', '3.0');

            $_SESSION[$offset] = $value;
        }

        public function offsetUnset($offset)
        {
            _doing_it_wrong('WP_Session::get_instance', 'Please use native Session functionality.', '3.0');

            unset($_SESSION[$offset]);
        }
    }
endif;
// phpcs:enable
