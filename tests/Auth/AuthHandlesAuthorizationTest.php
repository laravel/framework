<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\AuthorizationException;
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

    public function testDenyHasNullStatus()
    {
        $class = new class()
        {
            use HandlesAuthorization;

            public function __invoke()
            {
                return $this->deny('xxxx', 321);
            }
        };

        try {
            $class()->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertFalse($e->hasStatus());
            $this->assertNull($e->status());
        }
    }

    public function testItCanDenyWithStatus()
    {
        $class = new class()
        {
            use HandlesAuthorization;

            public function __invoke()
            {
                return $this->denyWithStatus(404);
            }
        };

        try {
            $class()->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertTrue($e->hasStatus());
            $this->assertSame(404, $e->status());
        }
    }
}
