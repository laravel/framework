<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\CacheStaticResponse;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CacheStaticResponseTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($this->container = new Container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(new Container);

        parent::tearDown();
    }

    public function testItStripsAllCookiesByDefault()
    {
        $response = (new CacheStaticResponse)->handle($this->request(), function () {
            return $this->responseWithCookies();
        });

        $this->assertSame([], $response->headers->getCookies());
    }

    public function testItStripsOnlyConfiguredCookies()
    {
        $response = (new CacheStaticResponse)->handle($this->request([
            'strip_cookies' => ['XSRF-TOKEN'],
        ]), function () {
            return $this->responseWithCookies();
        });

        $this->assertSame(['laravel_session'], $this->cookieNames($response));
    }

    public function testItSetsStaticCacheHeaders()
    {
        $response = (new CacheStaticResponse)->handle($this->request([
            'ttl' => 600,
            'browser_ttl' => 60,
        ]), function () {
            return new Response('Laravel');
        });

        $this->assertSame('max-age=60, public, s-maxage=600', $response->headers->get('Cache-Control'));
        $this->assertSame('public, max-age=600', $response->headers->get('CDN-Cache-Control'));
    }

    public function testItSetsZeroBrowserTtlByDefault()
    {
        $response = (new CacheStaticResponse)->handle($this->request(), function () {
            return new Response('Laravel');
        });

        $this->assertSame('max-age=0, public, s-maxage=3600', $response->headers->get('Cache-Control'));
    }

    public function testItCanDisableCdnCacheControlHeader()
    {
        $response = (new CacheStaticResponse)->handle($this->request([
            'cdn_cache_control' => false,
        ]), function () {
            return new Response('Laravel');
        });

        $this->assertNull($response->headers->get('CDN-Cache-Control'));
    }

    public function testItAlwaysAddsInertiaVaryHeader()
    {
        $response = (new CacheStaticResponse)->handle($this->request(), function () {
            return new Response('Laravel');
        });

        $this->assertSame('X-Inertia', $response->headers->get('Vary'));
    }

    public function testItMergesVaryHeaders()
    {
        $response = (new CacheStaticResponse)->handle($this->request([
            'vary' => ['Accept-Language', 'x-inertia'],
        ]), function () {
            return new Response('Laravel', 200, ['Vary' => 'Accept-Encoding']);
        });

        $this->assertSame('Accept-Encoding, Accept-Language, X-Inertia', $response->headers->get('Vary'));
    }

    public function testItDoesNotAddStaticHeadersForInertiaRequests()
    {
        $request = $this->request();
        $request->headers->set('X-Inertia', 'true');

        $response = (new CacheStaticResponse)->handle($request, function () {
            return $this->responseWithCookies();
        });

        $this->assertNull($response->headers->get('CDN-Cache-Control'));
        $this->assertSame(['laravel_session', 'XSRF-TOKEN'], $this->cookieNames($response));
    }

    public function testItDoesNotAddStaticHeadersForUncacheableMethods()
    {
        $response = (new CacheStaticResponse)->handle($this->request(method: 'POST'), function () {
            return $this->responseWithCookies();
        });

        $this->assertNull($response->headers->get('CDN-Cache-Control'));
        $this->assertSame(['laravel_session', 'XSRF-TOKEN'], $this->cookieNames($response));
    }

    public function testItDoesNotAddStaticHeadersForUncacheableStatuses()
    {
        $response = (new CacheStaticResponse)->handle($this->request(), function () {
            return $this->responseWithCookies(500);
        });

        $this->assertNull($response->headers->get('CDN-Cache-Control'));
        $this->assertSame(['laravel_session', 'XSRF-TOKEN'], $this->cookieNames($response));
    }

    public function testItDoesNotAddStaticHeadersForRedirectResponses()
    {
        $response = (new CacheStaticResponse)->handle($this->request(), function () {
            return new RedirectResponse('/login');
        });

        $this->assertNull($response->headers->get('CDN-Cache-Control'));
    }

    public function testItOverwritesPrivateCacheControlDirective()
    {
        $response = (new CacheStaticResponse)->handle($this->request(), function () {
            return new Response('Laravel', 200, ['Cache-Control' => 'private']);
        });

        $this->assertStringNotContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertSame('max-age=0, public, s-maxage=3600', $response->headers->get('Cache-Control'));
    }

    public function testItUsesConfiguredDefaults()
    {
        $this->container->instance('config', new Repository([
            'cache' => [
                'static' => [
                    'ttl' => 120,
                    'browser_ttl' => 10,
                    'vary' => ['Accept-Encoding'],
                ],
            ],
        ]));

        $response = (new CacheStaticResponse)->handle($this->request(), function () {
            return new Response('Laravel');
        });

        $this->assertSame('max-age=10, public, s-maxage=120', $response->headers->get('Cache-Control'));
        $this->assertSame('Accept-Encoding, X-Inertia', $response->headers->get('Vary'));
    }

    protected function request(array $options = [], string $method = 'GET')
    {
        $request = Request::create('/', $method);

        $route = new Route([$method], '/', fn () => 'Laravel');
        $route->setAction(array_merge($route->getAction(), ['static_cache' => $options]));

        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    protected function responseWithCookies(int $status = 200)
    {
        $response = new Response('Laravel', $status);

        $response->headers->setCookie(Cookie::create('laravel_session', 'session'));
        $response->headers->setCookie(Cookie::create('XSRF-TOKEN', 'token'));

        return $response;
    }

    protected function cookieNames(Response $response)
    {
        return array_map(fn ($cookie) => $cookie->getName(), $response->headers->getCookies());
    }
}
