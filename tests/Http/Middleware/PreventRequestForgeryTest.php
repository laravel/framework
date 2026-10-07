<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Exceptions\OriginMismatchException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Session\TokenMismatchException;
use JMac\Testing\Double;
use PHPUnit\Framework\TestCase;

class PreventRequestForgeryTest extends TestCase
{
    protected function tearDown(): void
    {
        PreventRequestForgery::flushState();
    }

    public function test_same_origin_header_passes()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'same-origin']);

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertSame('OK', $response->getContent());
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

        $this->assertSame('OK', $response->getContent());
    }

    public function test_cross_site_with_valid_token_passes()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'cross-site'], 'test-token');

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertSame('OK', $response->getContent());
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

        $this->assertSame('OK', $response->getContent());
    }

    public function test_query_request_without_token_fails()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'cross-site'], method: 'QUERY');

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_query_request_with_valid_token_passes()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'cross-site'], 'test-token', 'QUERY');

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    protected function createRequest(array $server = [], ?string $token = null, string $method = 'POST')
    {
        $request = Request::create(
            'http://example.com/test',
            $method,
            $token ? ['_token' => $token] : [],
            [],
            [],
            $server
        );

        $session = new Store('test', new ArraySessionHandler(10));
        $session->put('_token', 'test-token');
        $request->setLaravelSession($session);

        return $request;
    }

    protected function createMiddleware()
    {
        return new PreventRequestForgeryTestStub(
            Double::for(Application::class),
            new Encrypter(str_repeat('a', 16))
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
