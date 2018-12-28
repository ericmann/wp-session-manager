<?php

namespace EAMann\WPSession;

use EAMann\WPSession\Objects\Option;
use PHPUnit\Framework\TestCase;

class OptionTest extends TestCase
{
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
}