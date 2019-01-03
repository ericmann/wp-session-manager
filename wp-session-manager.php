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

if(!defined('WP_SESSION_MINIMUM_PHP_VERSION')) {
    define('WP_SESSION_MINIMUM_PHP_VERSION', '7.1.0');
}

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

    // Queue up the session stack.
    $wp_session_handler = EAMann\Sessionz\Manager::initialize();

    // Fall back to database storage where needed.
    if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
        $wp_session_handler->addHandler(new \EAMann\WPSession\OptionsHandler());
    } else {
        $wp_session_handler->addHandler(new \EAMann\WPSession\DatabaseHandler());
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

    // Create the required table.
    add_action('admin_init', ['EAMann\WPSession\DatabaseHandler', 'createTable']);
    add_action('wp_session_init', ['EAMann\WPSession\DatabaseHandler', 'createTable']);
    add_action('wp_install', ['EAMann\WPSession\DatabaseHandler', 'createTable']);
    register_activation_hook(__FILE__, ['EAMann\WPSession\DatabaseHandler', 'createTable']);
}

/**
 * Print an admin notice if we're on a bad version of PHP.
 */
function wp_session_manager_deactivated_notice() {
    $message = sprintf(
        __(
            'WP Session Manager requires PHP %s or newer. Your system is running PHP %s. Sessions are disabled. Please contact your system administrator to upgrade!',
            'wp-session-manager'
        ),
        WP_SESSION_MINIMUM_PHP_VERSION,
        PHP_VERSION
    );
    ?>
    <div class="notice notice-error">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php
}

/**
 * If a session hasn't already been started by some external system, start one!
 */
function wp_session_manager_start_session()
{
    $bootstrap = \EAMann\WPSession\DatabaseHandler::createTable();

    if (!is_wp_error($bootstrap) && session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

// WordPress won't enforce the minimum version of PHP for us, so we need to check.
if (version_compare(PHP_VERSION, WP_SESSION_MINIMUM_PHP_VERSION, '<')) {
    add_action('admin_notices', 'wp_session_manager_deactivated_notice');
} else {
    wp_session_manager_initialize();

    // Start up session management, if we're not in the CLI.
    if (!defined('WP_CLI') || false === WP_CLI) {
        add_action('plugins_loaded', 'wp_session_manager_start_session', 10, 0);
    }
}
