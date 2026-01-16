<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\OriginMismatchException;
use Illuminate\Session\TokenMismatchException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PreventRequestForgeryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        PreventRequestForgery::flushState();
    }

    public function test_same_origin_header_passes()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-origin']);

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_same_site_header_rejected_by_default()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-site']);

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_same_site_header_passes_when_allowed()
    {
        PreventRequestForgery::allowSameSite();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-site']);

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_cross_site_with_valid_token_passes()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'cross-site'], 'test-token');

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_cross_site_without_token_fails()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'cross-site']);

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_missing_header_without_token_fails()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest();

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_only_mode_rejects_cross_site()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        // Even with a valid token, origin-only mode rejects cross-site
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'cross-site'], 'test-token');

        $this->expectException(OriginMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_only_mode_rejects_missing_header()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest([], 'test-token');

        $this->expectException(OriginMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_only_mode_passes_same_origin()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-origin']);

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    protected function createRequest(array $server = [], ?string $token = null)
    {
        $request = Request::create(
            'http://example.com/test',
            'POST',
            $token ? ['_token' => $token] : [],
            [],
            [],
            $server
        );

        $session = m::mock(Session::class);
        $session->shouldReceive('token')->andReturn('test-token');
        $request->setLaravelSession($session);

        return $request;
    }

    protected function createMiddleware()
    {
        return new PreventRequestForgeryTestStub(
            m::mock(Application::class),
            m::mock(Encrypter::class)
        );
    }
}

class PreventRequestForgeryTestStub extends PreventRequestForgery
{
    protected $addHttpCookie = false;

    protected function runningUnitTests()
    {
        return false;
    }
}
