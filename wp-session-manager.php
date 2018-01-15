<?php
/**
 * Plugin Name: WP Session Manager
 * Plugin URI:  https://paypal.me/eam
 * Description: Prototype session management for WordPress.
 * Version:     3.0.0
 * Author:      Eric Mann
 * Author URI:  http://eamann.com
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
if (defined('WP_SESSION_USE_OPTIONS') && WP_SESSION_USE_OPTIONS) {
    $wp_session_handler->addHandler(new \EAMann\WPSession\OptionsHandler());
} else {
    $wp_session_handler->addHandler(new \EAMann\WPSession\DatabaseHandler());
}
$wp_session_handler->addHandler(new \EAMann\Sessionz\Handlers\MemoryHandler());

// Create the required table.
add_action('admin_init',      ['EAMann\WPSession\DatabaseHandler', 'create_table']);
add_action('wp_session_init', ['EAMann\WPSession\DatabaseHandler', 'create_table']);

// Start up session management, if we're not in the CLI
if (!defined('WP_CLI') || false === WP_CLI) {
    add_action('plugins_loaded', 'session_start');
}
