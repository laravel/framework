<?php

namespace Illuminate\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuthHandlesAuthorizationTest extends TestCase
{
    use HandlesAuthorization;

    public function test_allow_method()
    {
        $response = $this->allow('some message', 'some_code');

        $this->assertTrue($response->allowed());
        $this->assertFalse($response->denied());
        $this->assertEquals('some message', $response->message());
        $this->assertEquals('some_code', $response->code());
    }

    public function test_deny_method()
    {
        $response = $this->deny('some message', 'some_code');

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertEquals('some message', $response->message());
        $this->assertEquals('some_code', $response->code());
    }
}
