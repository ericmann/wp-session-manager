<?php

namespace EAMann\WPSession;

use PHPUnit\Framework\TestCase;

// Mocks of global WordPress functions, within our namespace, so we can fall back.

function apply_filters($filter, ...$args)
{
    return $args[0];
}

function wp_cache_set($id, $value, $group, $expires)
{
    return CacheHandlerTest::wp_cache_set($id, $value, $group, $expires);
}

function wp_cache_get($id, $group, $default = false)
{
    return CacheHandlerTest::wp_cache_get($id, $group, $default);
}

function wp_cache_delete($id, $group)
{
    return CacheHandlerTest::wp_cache_delete($id, $group);
}

class CacheHandlerTest extends TestCase
{
    /**
     * @var callable
     */
    protected static $set_handler;

    /**
     * @var callable
     */
    protected static $get_handler;

    /**
     * @var callable
     */
    protected static $delete_handler;

    public static function wp_cache_set($id, $value, $group, $expires)
    {
        if (is_callable(self::$set_handler)) {
            $handler = self::$set_handler;
            return $handler($id, $value, $group, $expires);
        }

        return false;
    }

    public static function wp_cache_get($id, $group, $default = false)
    {
        if (is_callable(self::$get_handler)) {
            $handler = self::$get_handler;
            return $handler($id, $group, $default);
        }

        return $default;
    }

    public static function wp_cache_delete($id, $group)
    {
        if (is_callable(self::$delete_handler)) {
            $handler = self::$delete_handler;
            return $handler($id, $group);
        }

        return false;
    }

    public function test_create()
    {
        $handler = new CacheHandler();

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
        $handler = new CacheHandler();

        $called = false;
        $callback = function($id, $data) use (&$called) {
            $this->assertEquals('id', $id);
            $this->assertEquals('data', $data);

            $called = true;
        };

        self::$set_handler = function($id, $value, $group, $expires) {
            return true;
        };

        $handler->write('id', 'data', $callback);

        $this->assertTrue($called);
    }

    public function test_hot_read()
    {
        $handler = new CacheHandler();

        self::$get_handler = function($id, $group) {
            return 'cached|data';
        };

        $this->assertEquals('cached|data', $handler->read('id', null));
    }

    public function test_cold_read()
    {
        $handler = new CacheHandler();

        self::$get_handler = function() {
            return false;
        };
        $set_called = false;
        self::$set_handler = function($id, $value, $group, $expires) use (&$set_called) {
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
        $handler = new CacheHandler();

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
        $handler = new CacheHandler();

        $called = false;
        $callback = function() use (&$called) {
            $called = true;
            return true;
        };

        $clean = $handler->clean(0, $callback);

        $this->assertTrue($called);
        $this->assertTrue($clean);
    }
}