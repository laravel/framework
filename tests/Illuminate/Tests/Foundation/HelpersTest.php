<?php

namespace Illuminate\Tests\Foundation;

use Orchestra\Testbench\TestCase;

class HelpersTest extends TestCase
{
    public function test_secure_url_can_return_string(): void
    {
        $this->assertIsString(secure_url('/'));
        $this->assertIsString(secure_url(null));
    }
}
