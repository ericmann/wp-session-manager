<?php

namespace EAMann\WPSession;

use PHPUnit\Framework\TestCase;
use EAMann\WPSession\Objects\Option;

// Mocks of global WordPress functions, within our namespace, so we can fall back.

function add_option($name, $data, $deprecated = '', $autoload = 'no')
{
    return OptionsHandlerTest::add_option($name, $data, $deprecated, $autoload);
}

function get_option($name, $default = false)
{
    return OptionsHandlerTest::get_option($name, $default);
}

function delete_option($name)
{
    return OptionsHandlerTest::delete_option($name);
}

class OptionsHandlerTest extends TestCase
{
    /**
     * @var callable
     */
    protected static $add_handler;

    /**
     * @var callable
     */
    protected static $get_handler;

    /**
     * @var callable
     */
    protected static $delete_handler;

    public static function add_option($name, $data, $deprecated = '', $autoload = 'no')
    {
        if (is_callable(self::$add_handler)) {
            $handler = self::$add_handler;
            return $handler($name, $data, $deprecated, $autoload);
        }

        return false;
    }

    public static function get_option($name, $default = false)
    {
        if (is_callable(self::$get_handler)) {
            $handler = self::$get_handler;
            return $handler($name, $default);
        }

        return $default;
    }

    public static function delete_option($name)
    {
        if (is_callable(self::$delete_handler)) {
            $handler = self::$delete_handler;
            return $handler($name);
        }

        return true;
    }

    public function test_create()
    {
        $handler = new OptionsHandler();

        $called = false;
        $callback = function($path, $name) use (&$called) {
            $this->assertEquals('path', $path);
            $this->assertEquals('name', $name);

            $called = true;
        };

        $handler->create('path', 'name', $callback);

        $this->assertTrue($called);
    }

    public function test_write()
    {
        $handler = new OptionsHandler();

        $called = false;
        $callback = function($id, $data) use (&$called) {
            $this->assertEquals('id', $id);
            $this->assertEquals('data', $data);

            $called = true;
        };

        self::$add_handler = function() {
            return true;
        };

        $handler->write('id', 'data', $callback);

        $this->assertTrue($called);
    }

    public function test_hot_read()
    {
        $handler = new OptionsHandler();

        self::$get_handler = function($name) {
            switch($name) {
                case '_wp_session_id':
                    return new Option('cached|data', time());;
                case '_wp_session_expires_id':
                    return time() + 500;
            }
        };

        $option = $handler->read('id', null);
        $this->assertEquals('cached|data', $option->data);
    }

    public function test_cold_read()
    {
        $handler = new OptionsHandler();

        self::$get_handler = function() {
            return false;
        };
        $set_called = false;
        self::$add_handler = function($name, $value) use (&$set_called) {
            $set_called = true;
            return true;
        };

        $called = false;
        $callback = function($id) use (&$called) {
            $called = true;
            return 'raw|data';
        };

        $data = $handler->read('id', $callback);

        $this->assertEquals('raw|data', $data);
        $this->assertTrue($set_called);
        $this->assertTrue($called);
    }

    public function test_delete()
    {
        $handler = new OptionsHandler();

        $called = false;
        $callback = function($id) use (&$called) {
            $called = true;
            return true;
        };

        $delete_called = false;
        self::$delete_handler = function() use (&$delete_called) {
            $delete_called = true;
            return true;
        };

        $deleted = $handler->delete('id', $callback);

        $this->assertTrue($deleted);
        $this->assertTrue($called);
        $this->assertTrue($delete_called);
    }

    public function test_clean()
    {
        global $wpdb;
        $oldWPdb = $wpdb;

        $wpdb = new class {
            public function prepare($statement, ...$args)
            {
                return $statement;
            }

            public function get_results($query)
            {
                return [];
            }
        };

        $handler = new OptionsHandler();

        $called = false;
        $callback = function() use (&$called) {
            $called = true;
            return true;
        };

        $clean = $handler->clean(0, $callback);

        $this->assertTrue($called);
        $this->assertTrue($clean);

        $wpdb = $oldWPdb;
    }
}