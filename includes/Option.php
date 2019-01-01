<?php
/**
 * Options table Session Data Wrapper
 *
 * Abstract the data into an object that can track its own expiration
 * timestamp for easier garbage collection.
 *
 * @package WP Session Manager
 * @subpackage Objects
 * @since 3.0
 */

namespace EAMann\WPSession\Objects;

/**
 * Class Option
 *
 * @package EAMann\WPSession\Objects
 *
 * @property-read string $data Data enclosed by the item
 * @property-read int $time Timestamp when the item was created
 */
class Option
{
    /**
     * Serialized data contained by the option.
     *
     * @var string
     */
    protected $_data;

    /**
     * Timestamp when the option was created.
     *
     * @var int
     */
    protected $_time;

    /**
     * Timestamp when the option expires.
     *
     * @var int
     */
    protected $_expires;

    /**
     * Option constructor.
     *
     * @param mixed $data Serialized data contained by the option.
     * @param int|null $time Optional timestamp for option creation.
     */
    public function __construct($data, int $time = null)
    {
        $this->_data = $data;
        $this->_time = null === $time ? time() : intval($time);

        $lifetime = intval(ini_get('session.gc_maxlifetime'));
        $this->_expires = $this->_time + $lifetime;
    }

    /**
     * Magic getter to allow read-only properties
     *
     * @param string $field Name of the field to retrieve.
     *
     * @return mixed
     */
    public function __get(string $field)
    {
        $field_name = "_$field";

        return isset($this->$field_name) ? $this->$field_name : null;
    }

    /**
     * Throw an exception when anyone tries to write anything.
     *
     * @param string $field Name of the field to set.
     * @param mixed $value Value to store.
     *
     * @throws \InvalidArgumentException Options in this context are read-only.
     */
    public function __set($field, $value)
    {
        throw new \InvalidArgumentException("Field `$field` is read-only!");
    }

    /**
     * Test whether an item is still valid
     *
     * @param int $lifetime Number of seconds for which the option is valid.
     * @param int $now Integer timestamp.
     *
     * @return bool
     */
    public function isValid($lifetime = null, $now = null)
    {
        if (null === $now) {
            $now = time();
        }

        if (null === $lifetime) {
            $lifetime = ini_get('session.gc_maxlifetime');
        }

        return (int)$now - $this->_time < (int)$lifetime;
    }
}
