WP Session Manager
==================

Prototype session management for WordPress.

Description
-----------

Adds `$_SESSION`-like functionality to WordPress.

Every visitor, logged in or not, will be issued an instance of `WP_Session`.  Their instance will be identified by an ID
stored in the `_wp_session` cookie.  Typically, session data will be stored in a WordPress transient, but if your
installation has a caching system in-place (i.e. memcached), the session data might be stored in memory.

This provides plugin and theme authors the ability to use WordPress-managed session variables without having to use the
standard PHP `$_SESSION` superglobal.

Installation
------------

**Manual Installation**

1. Upload the entire `/wp-session-manager` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Use `WP_Session::get_instance();` in your code.

Frequently Asked Questions
--------------------------

**How do I add session variables?**

First, make a reference to the WP_Session instance.  Then, use it like an associative array, just like `$_SESSION`:

```
$wp_session = WP_Session::get_instance();
$wp_session['user_name'] = 'User Name';                            // A string
$wp_session['user_contact'] = array( 'email' => 'user@name.com' ); // An array
$wp_session['user_obj'] = new WP_User( 1 );                        // An object
```

**How long do session variables live?**

By default, session variables will live for 24 minutes from the last time they were accessed - either read or write.

This value can be changed by using the `wp_session_expiration` filter:

`add_filter( 'wp_session_expiration', function() { return 60 * 60; } ); // Set expiration to 1 hour`

**Can I use this plugin without creating new tables?**

Absolutely! As of version 2.0, this plugin will create a new table for WordPress to store session data. In general, this is more efficient long-term than using options for data storage. However, if your system does not allow creating a table, add the following to `wp-config.php` to use the options table instead:

```
define( 'WP_SESSION_USE_OPTIONS', true );
```

Screenshots
-----------

None

Changelog
---------

**2.0.2**
- Fix: Wire the data storage migration to a session init hook to ensure it runs.
- Fix: Clean up sessions when all data is removed.

**2.0.1**
- Fix: Repair data storage that was not returning actual stored session data.

**2.0.0**
- Update: Use a table instead of options for storing session data.

**1.2.2**

- Update: Use regex pattern matching to ensure session IDs are identical going in/out of the DB to account for encoding differences

**1.2.1**

- Update: Additional filters for the `setcookie` parameters
- Update: Expose the Session ID publicly
- Fix: Better handling for malformed or broken session names

**1.2.0**

- Update: Enhanced plugin organization
- Update: Added WP_CLI support for session management
- Update: Add Composer definitions
- Fix: Break up the deletion of old sessions so queries don't time out under load

**1.1.2**

- Fix a race condition where session expiration options could accidentally be set to autoload
- Make the garbage collection routine run hourly to alleviate long-running tasks on larger sites

**1.1.1**

- Fix a bug where session expiration was not properly set upon instantiation

**1.1**

- Implement Recursive_ArrayAccess to provide multidimensional array support
- Better expiration for session data
- Implement garbage collection to keep the database clean

**1.0.2**

- Switch to object persistence rather than transients

**1.0.1**

- Changes implementation to avoid the use of a global variable (still registered for convenience)

**1.0**

- First version

Upgrade Notice
--------------

**2.0**

This version will create a new database table for storing session data! If you do not want such a table, please set the `WP_SESSION_USE_OPTIONS` constant to `true` in `wp-config.php`! Upgrading will delete all existing sessions!

**1.0**

First version

Additional Information
----------------------

**Contributors:** ericmann
**Donate link:** https://paypal.me/eam
**Tags:** session
**Requires at least:** 4.7
**Tested up to:** 4.9.1
**Stable tag:** 2.0.2
**License:** GPLv2 or later
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html
