<?php

namespace Illuminate\Tests\Auth;

use PHPUnit\Framework\TestCase;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\AuthorizationException;

class AuthAccessResponseTest extends TestCase
{
    public function test_allow_method()
    {
        $response = Response::allow('some message', 'some_code');

        $this->assertTrue($response->allowed());
        $this->assertFalse($response->denied());
        $this->assertEquals('some message', $response->message());
        $this->assertEquals('some_code', $response->code());
    }

    public function test_deny_method()
    {
        $response = Response::deny('some message', 'some_code');

        $this->assertTrue($response->denied());
        $this->assertFalse($response->allowed());
        $this->assertEquals('some message', $response->message());
        $this->assertEquals('some_code', $response->code());
    }

    public function test_authorize_method_throws_authorization_exception_when_response_denied()
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Some message.');
        $this->expectExceptionCode('some_code');

        $response = Response::deny('Some message.', 'some_code');

        $response->authorize();
    }

    public function test_throw_if_needed_doesnt_throw_authorization_exception_when_response_allowed()
    {
        $response = Response::allow('Some message.', 'some_code');

        $this->assertEquals($response, $response->authorize());
    }

    public function test_casting_to_string_returns_message()
    {
        $response = new Response('some data', true);
        $this->assertSame('some data', (string) $response);

        $response = new Response(null, false);
        $this->assertSame('', (string) $response);
    }

    public function test_response_to_array_method()
    {
        $response = new Response('Not allowed.', false, 'some_code');

        $this->assertEquals([
            'allowed' => false,
            'message' => 'Not allowed.',
            'code' => 'some_code',
        ], $response->toArray());
    }
}
