<?php
/**
 * WordPress session managment.
 *
 * Standardizes WordPress session data and uses either database transients or in-memory caching
 * for storing user session information.
 *
 * @package WordPress
 * @subpackage Session
 * @since   3.6.0
 */

/**
 * WordPress Session class for managing user session data.
 *
 * @package WordPress
 * @since   3.6.0
 */
class WP_Session implements ArrayAccess {
	/**
	 * Internal data collection.
	 *
	 * @var array
	 */
	private $container;

	/**
	 * ID of the current session.
	 *
	 * @var string
	 */
	private $session_id;

	/**
	 * Time in seconds until session data expired.
	 *
	 * @var int
	 */
	private $cache_expire;

	/**
	 * Singleton instance.
	 *
	 * @var bool|WP_Session
	 */
	private static $instance = false;

	/**
	 * Retrieve the current session instance.
	 *
	 * @param bool $session_id Session ID from which to populate data.
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
	 * @param $session_id
	 * @uses apply_filters Calls `wp_session_expiration` to determine how long until sessions expire.
	 */
	private function __construct() {
		if ( isset( $_COOKIE['_wp_session'] ) ) {
			$this->session_id = stripslashes( $_COOKIE['_wp_session'] );
		} else {
			$this->session_id = md5( uniqid() );
		}

		$this->read_data();

		$this->cache_expire = intval( apply_filters( 'wp_session_expiration', 24 * 60 ) );

		setcookie( '_wp_session', $this->session_id, time() + $this->cache_expire, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Read data from a transient for the current session.
	 *
	 * Automatically resets the expiration time for the session transient to some time in the future.
	 *
	 * @return array
	 */
	private function read_data() {
		$data = get_transient( "_wp_session_{$this->session_id}" );

		if ( ! $data ) {
			$data = array();
		}

		$this->container = $data;

		set_transient( "_wp_session_{$this->session_id}", $data, $this->cache_expire );

		return $data;
	}

	/**
	 * Write the data from the current session to the data storage system.
	 */
	public function write_data() {
		set_transient( "_wp_session_{$this->session_id}", $this->container, $this->cache_expire );
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
		return $this->cache_expire;
	}

	/**
	 * Whether a offset exists
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 *
	 * @param mixed $offset An offset to check for.
	 *
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists( $offset ) {
		return isset( $this->container[ $offset ]) ;
	}

	/**
	 * Offset to retrieve
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 *
	 * @param mixed $offset The offset to retrieve.
	 *
	 * @return mixed Can return all value types.
	 */
	public function offsetGet( $offset ) {
		return isset( $this->container[ $offset ] ) ? $this->container[ $offset ] : null;
	}

	/**
	 * Offset to set
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 *
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $value  The value to set.
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->container[] = $value;
		} else {
			$this->container[ $offset ] = $value;
		}
	}

	/**
	 * Offset to unset
	 *
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 *
	 * @param mixed $offset The offset to unset.
	 *
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		unset( $this->container[ $offset ] );
	}
}