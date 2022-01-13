<?php

namespace Illuminate\Tests\Integration\Support;

use Orchestra\Testbench\TestCase;

class HelpersTest extends TestCase
{
    public function testIsEnvironment()
    {
        $this->assertTrue(is_environment('testing'));
        $this->assertTrue(is_environment('foo', 'testing'));
        $this->assertTrue(is_environment(['foo', 'testing']));

        $this->assertFalse(is_environment('foo'));
        $this->assertFalse(is_environment('foo', 'bar'));
        $this->assertFalse(is_environment(['foo', 'bar']));
    }
}
