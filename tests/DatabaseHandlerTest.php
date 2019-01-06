<?php

namespace EAMann\WPSession;

use PHPUnit\Framework\TestCase;

class DatabaseHandlerTest extends TestCase
{

    public function test_create()
    {
        $handler = new DatabaseHandler();

        $called = false;
        $callback = function($path, $name) use (&$called) {
            $this->assertEquals('path', $path);
            $this->assertEquals('name', $name);

            $called = true;
        };

        $handler->create('path', 'name', $callback);

        $this->assertTrue($called);
    }

}