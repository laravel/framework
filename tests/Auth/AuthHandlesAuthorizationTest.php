<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\HandlesAuthorization;
use PHPUnit\Framework\TestCase;

class AuthHandlesAuthorizationTest extends TestCase
{
    use HandlesAuthorization;

    public function testAllowMethod()
    {
        $response = $this->allow('some message', 'some_code');

        $this->assertTrue($response->allowed());
        $this->assertFalse($response->denied());
        $this->assertSame('some message', $response->message());
        $this->assertSame('some_code', $response->code());
    }

    public function testDenyMethod()
    {
        $response = $this->deny('some message', 'some_code');

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertSame('some message', $response->message());
        $this->assertSame('some_code', $response->code());
    }

    public function testAllowIfMethodWhenFalse()
    {
        $response = $this->allowIf(false, 'some message', 'some_code');

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertSame('some message', $response->message());
        $this->assertSame('some_code', $response->code());
    }

    public function testAllowIfMethodWhenTrue()
    {
        $response = $this->allowIf(true);

        $this->assertTrue($response->allowed());
        $this->assertFalse($response->denied());
        $this->assertSame(null, $response->message());
        $this->assertSame(null, $response->code());
    }

    public function testDenyIfMethodWhenTrue()
    {
        $response = $this->denyIf(true, 'some message', 'some_code');

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertSame('some message', $response->message());
        $this->assertSame('some_code', $response->code());
    }

    public function testDenyIfMethodWhenFalse()
    {
        $response = $this->denyIf(false);

        $this->assertTrue($response->allowed());
        $this->assertFalse($response->denied());
        $this->assertSame(null, $response->message());
        $this->assertSame(null, $response->code());
    }
}
