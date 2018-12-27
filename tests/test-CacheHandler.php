<?php

use PHPUnit\Framework\TestCase;

class TestCacheHandler extends WP_UnitTestCase
{
    public function test_populate_data()
    {
        global $_wp_using_ext_object_cache;
        $old_using_ext_cache = $_wp_using_ext_object_cache;
        $_wp_using_ext_object_cache = true;

        $_SESSION['data'] = 'test';

        $session_id = session_id();

        $data = wp_cache_get("session_$session_id", 'sessions');

        $this->assertNotFalse($data);

        $_wp_using_ext_object_cache = $old_using_ext_cache;
    }
}