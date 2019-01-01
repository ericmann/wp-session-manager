<?php

namespace EAMann\WPSession;

use EAMann\WPSession\Objects\Option;
use PHPUnit\Framework\TestCase;

function ini_get($setting)
{
    return OptionTest::ini_get($setting);
}

class OptionTest extends TestCase
{
    private static $ini = [];

    public static function ini_get($setting)
    {
        if (isset(self::$ini[$setting])) {
            return self::$ini[$setting];
        }

        return null;
    }

    public function test_read_only()
    {
        $now = time();

        $option = new Option('data', $now);

        $this->assertEquals('data', $option->data);
        $this->assertEquals($now, $option->time);

        $thrown = false;
        try {
            $option->data = 'test';
        } catch (\Exception $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);

        $thrown = false;
        try {
            $option->time = time();
        } catch (\Exception $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }

    public function test_valid_option()
    {
        self::$ini['session.gc_maxlifetime'] = 500;

        $option = new Option('data', time());

        $this->assertTrue($option->isValid());
    }

    public function test_invalid_option()
    {
        $now = time();

        $option = new Option('data', $now - 500);

        $this->assertFalse($option->isValid(0, $now));
    }
}