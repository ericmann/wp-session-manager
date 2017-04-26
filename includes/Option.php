<?php
/**
 * Options table Session Data Wrapper
 *
 * Abstract the data into an object that can track its own expiration
 * timestamp for easier garbage collection.
 *
 * @package WP Session Manager
 * @subpackage Objects
 * @since 2.0
 */
namespace EAMann\Sessionz\Objects;

/**
 * Class Option
 * @package EAMann\Sessionz\Objects
 *
 * @property-read string $data Data enclosed by the item
 * @property-read int    $time Timestamp when the item was created
 */
class Option {
	/**
	 * @var string
	 */
	protected $_data;

	/**
	 * @var int
	 */
	protected $_time;

	public function __construct($data, $time = null)
	{
		$this->_data = $data;
		$this->_time = null === $time ? time() : (int) $time;
	}

	/**
	 * Magic getter to allow read-only properties
	 *
	 * @param string $field
	 *
	 * @return mixed
	 */
	public function __get($field)
	{
		$field_name = "_$field";

		return isset($this->$field_name) ? $this->$field_name : null;
	}

	/**
	 * Throw an exception when anyone tries to write anything.
	 *
	 * @param string $field
	 * @param mixed  $value
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __set($field, $value)
	{
		throw new \InvalidArgumentException("Field `$field` is read-only!");
	}

	/**
	 * Test whether an item is still valid
	 *
	 * @param int [$lifetime]
	 * @param int [$now]
	 *
	 * @return bool
	 */
	public function is_valid($lifetime = null, $now = null)
	{
		if (null === $now) $now = time();
		if (null === $lifetime) $lifetime = ini_get('session.gc_maxlifetime');

		return (int) $now - $this->_time < (int) $lifetime;
	}
}