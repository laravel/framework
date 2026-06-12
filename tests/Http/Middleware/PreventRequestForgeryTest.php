<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Exceptions\OriginMismatchException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PreventRequestForgeryTest extends TestCase
{
    protected function tearDown(): void
    {
        PreventRequestForgery::flushState();

        parent::tearDown();
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

    // Sec-Fetch-Site: none tests

    public function test_none_header_passes_in_default_mode()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'none']);

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_none_header_passes_in_origin_only_mode()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(['HTTP_SEC_FETCH_SITE' => 'none']);

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_absent_header_still_rejected_in_origin_only_mode()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(server: ['HTTPS' => 'on'], url: 'https://example.com/test');

        $this->expectException(OriginMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_get_request_with_none_header_still_passes()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTP_SEC_FETCH_SITE' => 'none'],
            method: 'GET',
        );

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    // Origin header fallback tests

    public function test_origin_fallback_passes_when_origin_matches_host()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTP_ORIGIN' => 'http://example.com'],
        );

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_origin_fallback_rejects_when_origin_does_not_match()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTP_ORIGIN' => 'https://evil.com'],
        );

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_fallback_rejects_null_origin()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTP_ORIGIN' => 'null'],
        );

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_fallback_rejects_when_no_origin_header()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest();

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_fallback_not_used_when_sec_fetch_site_present()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: [
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
                'HTTP_ORIGIN' => 'http://example.com',
            ],
        );

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_fallback_not_used_when_sec_fetch_site_same_origin()
    {
        $middleware = $this->createMiddleware();
        // Origin doesn't match, but Sec-Fetch-Site: same-origin should pass
        // without ever consulting the Origin header.
        $request = $this->createRequest(
            server: [
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_ORIGIN' => 'https://evil.com',
            ],
        );

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_origin_fallback_normalizes_default_ports()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTPS' => 'on', 'HTTP_ORIGIN' => 'https://example.com'],
            url: 'https://example.com:443/test',
        );

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_origin_fallback_rejects_port_mismatch()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTPS' => 'on', 'HTTP_ORIGIN' => 'https://example.com:8443'],
            url: 'https://example.com/test',
        );

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_fallback_rejects_scheme_mismatch()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTPS' => 'on', 'HTTP_ORIGIN' => 'http://example.com'],
            url: 'https://example.com/test',
        );

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_origin_fallback_is_case_insensitive()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTP_ORIGIN' => 'http://Example.COM'],
        );

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_origin_fallback_passes_in_origin_only_mode()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTPS' => 'on', 'HTTP_ORIGIN' => 'https://example.com'],
            url: 'https://example.com/test',
        );

        $response = $middleware->handle($request, fn () => new Response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_origin_fallback_mismatch_throws_in_origin_only_mode()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTPS' => 'on', 'HTTP_ORIGIN' => 'https://evil.com'],
            url: 'https://example.com/test',
        );

        $this->expectException(OriginMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_no_origin_throws_in_origin_only_mode()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTPS' => 'on'],
            url: 'https://example.com/test',
        );

        $this->expectException(OriginMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    // HTTP error message tests

    public function test_origin_only_http_missing_header_shows_helpful_message()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest();

        try {
            $middleware->handle($request, fn () => new Response('OK'));
            $this->fail('Expected OriginMismatchException');
        } catch (OriginMismatchException $e) {
            $this->assertStringContainsString('secure connection', $e->getMessage());
        }
    }

    public function test_origin_only_https_missing_header_shows_generic_message()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTPS' => 'on'],
            url: 'https://example.com/test',
        );

        try {
            $middleware->handle($request, fn () => new Response('OK'));
            $this->fail('Expected OriginMismatchException');
        } catch (OriginMismatchException $e) {
            $this->assertEquals('Origin mismatch.', $e->getMessage());
        }
    }

    public function test_origin_only_http_cross_site_shows_generic_message()
    {
        PreventRequestForgery::useOriginOnly();

        $middleware = $this->createMiddleware();
        $request = $this->createRequest(
            server: ['HTTP_SEC_FETCH_SITE' => 'cross-site'],
        );

        try {
            $middleware->handle($request, fn () => new Response('OK'));
            $this->fail('Expected OriginMismatchException');
        } catch (OriginMismatchException $e) {
            $this->assertEquals('Origin mismatch.', $e->getMessage());
        }
    }

    public function test_default_mode_http_unchanged()
    {
        $middleware = $this->createMiddleware();
        $request = $this->createRequest();

        $this->expectException(TokenMismatchException::class);

        $middleware->handle($request, fn () => new Response('OK'));
    }

    protected function createRequest(array $server = [], ?string $token = null, string $url = 'http://example.com/test', string $method = 'POST')
    {
        $request = Request::create(
            $url,
            $method,
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
