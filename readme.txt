=== WP Session Manager ===
Contributors:      ericmann
Donate link:       https://paypal.me/eam
Tags:              session
Requires at least: 4.7
Tested up to:      4.9.2
Requires PHP:      5.6
Stable tag:        3.0.4
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Session management for WordPress.

== Description ==

Adds `$_SESSION` functionality to WordPress, leveraging the database where needed to power multi-server installations.

Every visitor, logged in or not, will be issued a session. Session data will be stored in the WordPress database by default
to deal with load balancing issues if multiple application servers are being used. In addition, the session collection will
also be stored _in memory_ for rapid use within WordPress.

Session data stored in the database can be encrypted at rest for better security.

== Installation ==

= Manual Installation =

1. Upload the entire `/wp-session-manager` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Use `$_SESSION` in your code.

== Frequently Asked Questions ==

= How do I add session variables? =

Merely use the superglobal `$_SESSION` array:

`
$_SESSION['user_name'] = 'User Name';                            // A string
$_SESSION['user_contact'] = array( 'email' => 'user@name.com' ); // An array
$_SESSION['user_obj'] = new WP_User( 1 );                        // An object
`

= How long do session variables live? =

This depends on your PHP installation's configuration. Please read the [PHP manual](http://php.net/manual/en/session.configuration.php)
for more details on configuration.

= Can I use this plugin without creating new tables? =

Absolutely! As of version 2.0, this plugin will create a new table for WordPress to store session data. In general, this is more efficient long-term than using options for data storage. However, if your system does not allow creating a table, add the following to `wp-config.php` to use the options table instead:

`
define( 'WP_SESSION_USE_OPTIONS', true );
`

== Screenshots ==

None

== Changelog ==

= 3.0.4 =
* Update: Add support for the `wp_install` hook to create custom table immediately.

= 3.0.3 =
* Fix: Repair code blocks in the readme
* Fix: Use a more defensive approach to starting sessions in the event another plugin has started one already

= 3.0.2 =
* Fix: Add back in proper array access support for the deprecated `WP_Session` object.

= 3.0.1 =
* Update: Pull a Sessionz fix

= 3.0.0 =
* Update: Refactor to use Sessionz
* Update: Add encryption at rest if `WP_SESSION_ENC_KEY` is set

= 2.0.2 =
* Fix: Wire the data storage migration to a session init hook to ensure it runs.
* Fix: Clean up sessions when all data is removed.

= 2.0.1 =
* Fix: Repair data storage that was not returning actual stored session data.

= 2.0.0 =
* Update: Use a table instead of options for storing session data.

= 1.2.2 =
* Update: Use regex pattern matching to ensure session IDs are identical going in/out of the DB to account for encoding differences

= 1.2.1 =
* Update: Additional filters for the `setcookie` parameters
* Update: Expose the Session ID publicly
* Fix: Better handling for malformed or broken session names

= 1.2.0 =
* Update: Enhanced plugin organization
* Update: Added WP_CLI support for session management
* Update: Add Composer definitions
* Fix: Break up the deletion of old sessions so queries don't time out under load

= 1.1.2 =
* Fix a race condition where session expiration options could accidentally be set to autoload
* Make the garbage collection routine run hourly to alleviate long-running tasks on larger sites

= 1.1.1 =
* Fix a bug where session expiration was not properly set upon instantiation

= 1.1 =
* Implement Recursive_ArrayAccess to provide multidimensional array support
* Better expiration for session data
* Implement garbage collection to keep the database clean

= 1.0.2 =
* Switch to object persistence rather than transients

= 1.0.1 =
* Changes implementation to avoid the use of a global variable (still registered for convenience)

= 1.0 =
* First version

== Upgrade Notice ==

= 3.0 =
This version requires PHP 5.6 or higher and uses Composer-powered autoloading to incorporate Sessionz for transparent session management.

= 2.0 =
This version will create a new database table for storing session data! If you do not want such a table, please set the `WP_SESSION_USE_OPTIONS` constant to `true` in `wp-config.php`! Upgrading will delete all existing sessions!

= 1.0 =
First version
