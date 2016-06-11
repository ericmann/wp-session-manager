<?php
/**
 * WordPress session management.
 *
 * Standardizes WordPress session data using database-backed options for storage.
 * for storing user session information.
 *
 * @package WordPress
 * @subpackage Session
 * @since   3.7.0
 */

/**
 * WordPress Session class for managing user session data.
 *
 * @package WordPress
 * @since   3.7.0
 */
final class WP_Session extends Recursive_ArrayAccess {
	/**
	 * ID of the current session.
	 *
	 * @var string
	 */
	public $session_id;

	/**
	 * Unix timestamp when session expires.
	 *
	 * @var int
	 */
	protected $expires;

	/**
	 * Unix timestamp indicating when the expiration time needs to be reset.
	 *
	 * @var int
	 */
	protected $exp_variant;

	/**
	 * Singleton instance.
	 *
	 * @var bool|WP_Session
	 */
	private static $instance = false;

	/**
	 * Retrieve the current session instance.
	 *
	 * @return bool|WP_Session
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Default constructor.
	 * Will rebuild the session collection from the given session ID if it exists. Otherwise, will
	 * create a new session with that ID.
	 *
	 * @uses apply_filters Calls `wp_session_expiration` to determine how long until sessions expire.
	 */
	protected function __construct() {
		if ( isset( $_COOKIE[WP_SESSION_COOKIE] ) ) {
			$cookie = stripslashes( $_COOKIE[WP_SESSION_COOKIE] );
			$cookie_crumbs = explode( '||', $cookie );

			$this->session_id = $cookie_crumbs[0];
			$this->expires = $cookie_crumbs[1];
			$this->exp_variant = $cookie_crumbs[2];

			// Update the session expiration if we're past the variant time
			if ( time() > $this->exp_variant ) {
				$this->set_expiration();
				delete_option( "_wp_session_expires_{$this->session_id}" );
				add_option( "_wp_session_expires_{$this->session_id}", $this->expires, '', 'no' );
			}
		} else {
			$this->session_id = WP_Session_Utils::generate_id();
			$this->set_expiration();
		}

		$this->read_data();

		$this->set_cookie();

	}

	/**
	 * Set both the expiration time and the expiration variant.
	 *
	 * If the current time is below the variant, we don't update the session's expiration time. If it's
	 * greater than the variant, then we update the expiration time in the database.  This prevents
	 * writing to the database on every page load for active sessions and only updates the expiration
	 * time if we're nearing when the session actually expires.
	 *
	 * By default, the expiration time is set to 30 minutes.
	 * By default, the expiration variant is set to 24 minutes.
	 *
	 * As a result, the session expiration time - at a maximum - will only be written to the database once
	 * every 24 minutes.  After 30 minutes, the session will have been expired. No cookie will be sent by
	 * the browser, and the old session will be queued for deletion by the garbage collector.
	 *
	 * @uses apply_filters Calls `wp_session_expiration_variant` to get the max update window for session data.
	 * @uses apply_filters Calls `wp_session_expiration` to get the standard expiration time for sessions.
	 */
	protected function set_expiration() {
		$this->exp_variant = time() + (int) apply_filters( 'wp_session_expiration_variant', 24 * 60 );
		$this->expires = time() + (int) apply_filters( 'wp_session_expiration', 30 * 60 );
	}

	/**
	* Set the session cookie
	* @uses apply_filters Calls `wp_session_cookie_secure` to set the $secure parameter of setcookie()
	* @uses apply_filters Calls `wp_session_cookie_httponly` to set the $httponly parameter of setcookie()
	 * @return boolean 	from http://php.net/manual/en/function.setcookie.php :
	 * 									If output exists prior to calling this function, setcookie() will fail and return FALSE.
	 * 									If setcookie() successfully runs, it will return TRUE.
	 * 									This does not indicate whether the user accepted the cookie.
	 *
	 */
	protected function set_cookie() {
		$secure = apply_filters('wp_session_cookie_secure', false);
		$httponly = apply_filters('wp_session_cookie_httponly', false);
		return setcookie( WP_SESSION_COOKIE, $this->session_id . '||' . $this->expires . '||' . $this->exp_variant , $this->expires, COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly );
	}

	/**
	 * Read data from a transient for the current session.
	 *
	 * Automatically resets the expiration time for the session transient to some time in the future.
	 *
	 * @return array
	 */
	protected function read_data() {
		$this->container = get_option( "_wp_session_{$this->session_id}", array() );

		return $this->container;
	}

	/**
	 * Write the data from the current session to the data storage system.
	 */
	public function write_data() {
		$option_key = "_wp_session_{$this->session_id}";

		if ( false === get_option( $option_key ) ) {
			add_option( "_wp_session_{$this->session_id}", $this->container, '', 'no' );
			add_option( "_wp_session_expires_{$this->session_id}", $this->expires, '', 'no' );
		} else {
			delete_option( "_wp_session_{$this->session_id}" );
			add_option( "_wp_session_{$this->session_id}", $this->container, '', 'no' );
		}
	}

	/**
	 * Output the current container contents as a JSON-encoded string.
	 *
	 * @return string
	 */
	public function json_out() {
		return json_encode( $this->container );
	}

	/**
	 * Decodes a JSON string and, if the object is an array, overwrites the session container with its contents.
	 *
	 * @param string $data
	 *
	 * @return bool
	 */
	public function json_in( $data ) {
		$array = json_decode( $data );

		if ( is_array( $array ) ) {
			$this->container = $array;
			return true;
		}

		return false;
	}

	/**
	 * Regenerate the current session's ID.
	 *
	 * @param bool $delete_old Flag whether or not to delete the old session data from the server.
	 */
	public function regenerate_id( $delete_old = false ) {
		if ( $delete_old ) {
			delete_option( "_wp_session_{$this->session_id}" );
		}

		$this->session_id = WP_Session_Utils::generate_id();

		$this->set_cookie();
	}

	/**
	 * Check if a session has been initialized.
	 *
	 * @return bool
	 */
	public function session_started() {
		return !!self::$instance;
	}

	/**
	 * Return the read-only cache expiration value.
	 *
	 * @return int
	 */
	public function cache_expiration() {
		return $this->expires;
	}

	/**
	 * Flushes all session variables.
	 */
	public function reset() {
		$this->container = array();
	}
}
