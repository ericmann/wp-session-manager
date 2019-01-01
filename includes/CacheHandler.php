<?php
/**
 * WordPress Object Cache Session Handler
 *
 * This class uses the WordPress object cache to store session data.
 *
 * @package WP Session Manager
 * @subpackage Handlers
 * @since 4.0
 */

namespace EAMann\WPSession;

/**
 * Use the WordPress External Object Cache (if available) to store data and avoid
 * a round-trip to the database.
 */
class CacheHandler extends SessionHandler
{

    /**
     * Get the default lifetime of a session in seconds.
     *
     * @return int
     */
    private function getExpiration(): int
    {
        $expires = intval(ini_get('session.gc_maxlifetime'));

        /**
         * Filter the number of seconds until a cached session value should be removed
         * from the object cache.
         *
         * @param int $expires
         */
        return intval(apply_filters('wp_session_cache_expiration', $expires));
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
     * Store the item in the cache and then pass the data, unchanged, down
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
        $session_key = $this->sanitize($key);

        wp_cache_set("session_${session_key}", $data, 'sessions', $this->getExpiration());

        return $next($key, $data);
    }

    /**
     * Grab the item from the cache if it exists, otherwise delve deeper
     * into the stack and retrieve from another underlying middleware.
     *
     * @param string   $key  Session identifier.
     * @param callable $next Next read operation in the stack, might not be needed.
     *
     * @return mixed
     */
    public function read($key, $next)
    {
        $session_key = $this->sanitize($key);

        $data = wp_cache_get("session_${session_key}", 'sessions');
        if (false === $data) {
            // Passing the key unsanitized to the next handler to avoid weirdness.
            $data = $next($key);
            if (false !== $data) {
                wp_cache_set("session_${session_key}", $data, 'sessions', $this->getExpiration());
            }
        }

        return $data;
    }

    /**
     * Purge an item from the cache immediately.
     *
     * @param string   $key  Session identifier.
     * @param callable $next Next delete operation in the stack.
     *
     * @return mixed
     */
    public function delete($key, $next)
    {
        $session_key = $this->sanitize($key);

        wp_cache_delete("session_${session_key}", 'sessions');

        return $next($key);
    }

    /**
     * We expect the external cache to expire items on its own, so this is a noop.
     *
     * @param int      $maxlifetime Maximum number of seconds for which a session can live.
     * @param callable $next        Next clean operation in the stack.
     *
     * @return mixed
     */
    public function clean($maxlifetime, $next)
    {
        return $next($maxlifetime);
    }
}
