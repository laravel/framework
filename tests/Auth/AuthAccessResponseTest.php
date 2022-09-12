<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use PHPUnit\Framework\TestCase;

class AuthAccessResponseTest extends TestCase
{
    public function testAllowMethod()
    {
        $response = Response::allow('some message', 'some_code');

        $this->assertTrue($response->allowed());
        $this->assertFalse($response->denied());
        $this->assertSame('some message', $response->message());
        $this->assertSame('some_code', $response->code());
    }

    public function testDenyMethod()
    {
        $response = Response::deny('some message', 'some_code');

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertSame('some message', $response->message());
        $this->assertSame('some_code', $response->code());
    }

    public function testDenyMethodWithNoMessageReturnsNull()
    {
        $response = Response::deny();

        $this->assertNull($response->message());
    }

    public function testItSetsEmptyStatusOnExceptionWhenAuthorizing()
    {
        try {
            Response::deny('foo', 3)->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertNull($e->status());
            $this->assertFalse($e->hasStatus());
            $this->assertSame('foo', $e->response()->message());
            $this->assertSame('foo', $e->getMessage());
            $this->assertSame(3, $e->getCode());
        }
    }

    public function testItSetsStatusOnExceptionWhenAuthorizing()
    {
        try {
            Response::deny('foo', 3)->withStatus(418)->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertSame(418, $e->status());
            $this->assertTrue($e->hasStatus());
            $this->assertSame('foo', $e->response()->message());
            $this->assertSame('foo', $e->getMessage());
            $this->assertSame(3, $e->getCode());
        }

        try {
            Response::deny('foo', 3)->asNotFound()->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertSame(404, $e->status());
            $this->assertTrue($e->hasStatus());
            $this->assertSame('foo', $e->response()->message());
            $this->assertSame('foo', $e->getMessage());
            $this->assertSame(3, $e->getCode());
        }

        try {
            Response::denyWithStatus(444)->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertSame(444, $e->status());
            $this->assertTrue($e->hasStatus());
            $this->assertNull($e->response()->message());
            $this->assertSame('This action is unauthorized.', $e->getMessage());
            $this->assertSame(0, $e->getCode());
        }

        try {
            Response::denyWithStatus(444, 'foo', 3)->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertSame(444, $e->status());
            $this->assertTrue($e->hasStatus());
            $this->assertSame('foo', $e->response()->message());
            $this->assertSame('foo', $e->getMessage());
            $this->assertSame(3, $e->getCode());
        }

        try {
            Response::denyAsNotFound()->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertSame(404, $e->status());
            $this->assertTrue($e->hasStatus());
            $this->assertNull($e->response()->message());
            $this->assertSame('This action is unauthorized.', $e->getMessage());
            $this->assertSame(0, $e->getCode());
        }

        try {
            Response::denyAsNotFound('foo', 3)->authorize();
            $this->fail();
        } catch (AuthorizationException $e) {
            $this->assertSame(404, $e->status());
            $this->assertTrue($e->hasStatus());
            $this->assertSame('foo', $e->response()->message());
            $this->assertSame('foo', $e->getMessage());
            $this->assertSame(3, $e->getCode());
        }
    }

    public function testAuthorizeMethodThrowsAuthorizationExceptionWhenResponseDenied()
    {
        $response = Response::deny('Some message.', 'some_code');

        try {
            $response->authorize();
        } catch (AuthorizationException $e) {
            $this->assertSame('Some message.', $e->getMessage());
            $this->assertSame('some_code', $e->getCode());
            $this->assertEquals($response, $e->response());
        }
    }

    public function testAuthorizeMethodThrowsAuthorizationExceptionWithDefaultMessage()
    {
        $response = Response::deny();

        try {
            $response->authorize();
        } catch (AuthorizationException $e) {
            $this->assertSame('This action is unauthorized.', $e->getMessage());
        }
    }

    public function testThrowIfNeededDoesntThrowAuthorizationExceptionWhenResponseAllowed()
    {
        $response = Response::allow('Some message.', 'some_code');

        $this->assertEquals($response, $response->authorize());
    }

    public function testCastingToStringReturnsMessage()
    {
        $response = new Response(true, 'some data');
        $this->assertSame('some data', (string) $response);

        $response = new Response(false, null);
        $this->assertSame('', (string) $response);
    }

    public function testResponseToArrayMethod()
    {
        $response = new Response(false, 'Not allowed.', 'some_code');

        $this->assertEquals([
            'allowed' => false,
            'message' => 'Not allowed.',
            'code' => 'some_code',
        ], $response->toArray());
    }
}
