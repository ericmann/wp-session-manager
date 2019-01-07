<?php
/**
 * Plugin Name: WP Session Manager
 * Plugin URI:  https://paypal.me/eam
 * Description: Session management for WordPress.
 * Version:     4.1.0
 * Author:      Eric Mann
 * Author URI:  https://eamann.com
 * License:     GPLv2+
 *
 * @package WP Session Manager
 */

if (!defined('WP_SESSION_MINIMUM_PHP_VERSION')) {
    define('WP_SESSION_MINIMUM_PHP_VERSION', '7.1.0');
}

$wp_session_messages = [
    'bad_php_version' => sprintf(
        __(
            'WP Session Manager requires PHP %s or newer. Please contact your system administrator to upgrade!',
            'wp-session-manager'
        ),
        WP_SESSION_MINIMUM_PHP_VERSION,
        PHP_VERSION
    ),
    'multiple_sessions' => __(
        'Another plugin is attempting to start a session with WordPress. WP Session Manager will not work!',
        'wp-session-manager'
    )
];

/**
 * Initialize the plugin, bootstrap autoloading, and register default hooks
 */
function wp_session_manager_initialize()
{
    $wp_session_autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($wp_session_autoload)) {
        require_once $wp_session_autoload;
    }

    if (!class_exists('EAMann\Sessionz\Manager')) {
        exit('WP Session Manager requires Composer autoloading, which is not configured');
    }

    if (!isset($_SESSION)) {
        // Queue up the session stack.
        $wp_session_handler = EAMann\Sessionz\Manager::initialize();

        // Fall back to database storage where needed.
        if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
            $wp_session_handler->addHandler(new \EAMann\WPSession\OptionsHandler());
        } else {
            $wp_session_handler->addHandler(new \EAMann\WPSession\DatabaseHandler());

            /**
             * The database handler can automatically clean up sessions as it goes. By default,
             * we'll run the cleanup routine every hour to catch any stale sessions that PHP's
             * garbage collector happens to miss. This timeout can be filtered to increase or
             * decrease the frequency of the manual purge.
             *
             * @param string $timeout Interval with which to purge stale sessions
             */
            $timeout = apply_filters('wp_session_gc_interval', 'hourly');

            if (!wp_next_scheduled('wp_session_database_gc')) {
                wp_schedule_event(time(), $timeout, 'wp_session_database_gc');
            }

            add_action('wp_session_database_gc', ['EAMann\WPSession\DatabaseHandler', 'directClean']);
        }

        // If we have an external object cache, let's use it!
        if (wp_using_ext_object_cache()) {
            $wp_session_handler->addHandler(new EAMann\WPSession\CacheHandler());
        }

        // Decrypt the data surfacing from external storage.
        if (defined('WP_SESSION_ENC_KEY') && WP_SESSION_ENC_KEY) {
            $wp_session_handler->addHandler(new \EAMann\Sessionz\Handlers\EncryptionHandler(WP_SESSION_ENC_KEY));
        }

        // Use an in-memory cache for the instance if we can. This will only help in rare cases.
        $wp_session_handler->addHandler(new \EAMann\Sessionz\Handlers\MemoryHandler());

        $_SESSION['wp_session_manager'] = 'active';
    }

    if (! isset($_SESSION['wp_session_manager']) || $_SESSION['wp_session_manager'] !== 'active') {
        add_action('admin_notices', 'wp_session_manager_multiple_sessions_notice');
        return;
    }

    // Create the required table.
    \EAMann\WPSession\DatabaseHandler::createTable();

    register_deactivation_hook(__FILE__, function () {
        wp_clear_scheduled_hook('wp_session_database_gc');
    });
}

/**
 * Print an admin notice if too many plugins are manipulating sessions.
 *
 * @global array $wp_session_messages
 */
function wp_session_manager_multiple_sessions_notice()
{
    global $wp_session_messages;
    ?>
    <div class="notice notice-error">
        <p><?php echo esc_html($wp_session_messages['multiple_sessions']); ?></p>
    </div>
    <?php
}

/**
 * Print an admin notice if we're on a bad version of PHP.
 *
 * @global array $wp_session_messages
 */
function wp_session_manager_deactivated_notice()
{
    global $wp_session_messages;
    ?>
    <div class="notice notice-error">
        <p><?php echo esc_html($wp_session_messages['bad_php_version']); ?></p>
    </div>
    <?php
}

/**
 * If a session hasn't already been started by some external system, start one!
 */
function wp_session_manager_start_session()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

// WordPress won't enforce the minimum version of PHP for us, so we need to check.
if (version_compare(PHP_VERSION, WP_SESSION_MINIMUM_PHP_VERSION, '<')) {
    add_action('admin_notices', 'wp_session_manager_deactivated_notice');
} else {
    add_action('plugins_loaded', 'wp_session_manager_initialize', 1, 0);

    // Start up session management, if we're not in the CLI.
    if (!defined('WP_CLI') || false === WP_CLI) {
        add_action('plugins_loaded', 'wp_session_manager_start_session', 10, 0);
    }
}
