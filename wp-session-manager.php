<?php
/**
 * Plugin Name: WP Session Manager
 * Plugin URI:  https://paypal.me/eam
 * Description: Session management for WordPress.
 * Version:     4.0.0
 * Author:      Eric Mann
 * Author URI:  https://eamann.com
 * License:     GPLv2+
 */

$wp_session_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($wp_session_autoload)) {
    require_once $wp_session_autoload;
}

if (!class_exists('EAMann\Sessionz\Manager')) {
    exit('WP Session Manager requires Composer autoloading, which is not configured');
}

// Queue up the session stack
$wp_session_handler = EAMann\Sessionz\Manager::initialize();

// Fall back to database storage where needed
if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
    $wp_session_handler->addHandler(new \EAMann\WPSession\OptionsHandler());
} else {
    $wp_session_handler->addHandler(new \EAMann\WPSession\DatabaseHandler());
}

// If we have an external object cache, let's use it!
if (wp_using_ext_object_cache()) {
    $wp_session_handler->addHandler(new EAMann\WPSession\CacheHandler());
}

// Decrypt the data surfacing from external storage
if (defined('WP_SESSION_ENC_KEY') && WP_SESSION_ENC_KEY) {
    $wp_session_handler->addHandler(new \EAMann\Sessionz\Handlers\EncryptionHandler(WP_SESSION_ENC_KEY));
}

// Use an in-memory cache for the instance if we can. This will only help in rare cases.
$wp_session_handler->addHandler(new \EAMann\Sessionz\Handlers\MemoryHandler());

// Create the required table.
add_action('admin_init',      ['EAMann\WPSession\DatabaseHandler', 'create_table']);
add_action('wp_session_init', ['EAMann\WPSession\DatabaseHandler', 'create_table']);
add_action('wp_install',      ['EAMann\WPSession\DatabaseHandler', 'create_table']);
register_activation_hook(__FILE__, ['EAMann\WPSession\DatabaseHandler', 'create_table']);

/**
 * If a session hasn't already been started by some external system, start one!
 */
function wp_session_manager_start_session()
{
    \EAMann\WPSession\DatabaseHandler::create_table();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

// Start up session management, if we're not in the CLI
if (!defined('WP_CLI') || false === WP_CLI) {
    add_action('plugins_loaded', 'wp_session_manager_start_session', 10, 0);
}
