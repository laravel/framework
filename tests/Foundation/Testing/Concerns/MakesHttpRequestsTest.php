<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Orchestra\Testbench\TestCase;

class MakesHttpRequestsTest extends TestCase
{
    public function testFromSetsHeaderAndSession()
    {
        $this->from('previous/url');

        $this->assertSame('previous/url', $this->defaultHeaders['referer']);
        $this->assertSame('previous/url', $this->app['session']->previousUrl());
    }

    public function testWithTokenSetsAuthorizationHeader()
    {
        $this->withToken('foobar');
        $this->assertSame('Bearer foobar', $this->defaultHeaders['Authorization']);

        $this->withToken('foobar', 'Basic');
        $this->assertSame('Basic foobar', $this->defaultHeaders['Authorization']);
    }

    public function testWithBasicAuthSetsAuthorizationHeader()
    {
        $callback = function ($username, $password) {
            return base64_encode("$username:$password");
        };

        $username = 'foo';
        $password = 'bar';

        $this->withBasicAuth($username, $password);
        $this->assertSame('Basic '.$callback($username, $password), $this->defaultHeaders['Authorization']);

        $password = 'buzz';

        $this->withBasicAuth($username, $password);
        $this->assertSame('Basic '.$callback($username, $password), $this->defaultHeaders['Authorization']);
    }

    public function testWithoutTokenRemovesAuthorizationHeader()
    {
        $this->withToken('foobar');
        $this->assertSame('Bearer foobar', $this->defaultHeaders['Authorization']);

        $this->withoutToken();
        $this->assertArrayNotHasKey('Authorization', $this->defaultHeaders);
    }

    public function testWithoutAndWithMiddleware()
    {
        $this->assertFalse($this->app->has('middleware.disable'));

        $this->withoutMiddleware();
        $this->assertTrue($this->app->has('middleware.disable'));
        $this->assertTrue($this->app->make('middleware.disable'));

        $this->withMiddleware();
        $this->assertFalse($this->app->has('middleware.disable'));
    }

    public function testWithoutAndWithMiddlewareWithParameter()
    {
        $next = function ($request) {
            return $request;
        };

        $this->assertFalse($this->app->has(MyMiddleware::class));
        $this->assertSame(
            'fooWithMiddleware',
            $this->app->make(MyMiddleware::class)->handle('foo', $next)
        );

        $this->withoutMiddleware(MyMiddleware::class);
        $this->assertTrue($this->app->has(MyMiddleware::class));
        $this->assertSame(
            'foo',
            $this->app->make(MyMiddleware::class)->handle('foo', $next)
        );

        $this->withMiddleware(MyMiddleware::class);
        $this->assertFalse($this->app->has(MyMiddleware::class));
        $this->assertSame(
            'fooWithMiddleware',
            $this->app->make(MyMiddleware::class)->handle('foo', $next)
        );
    }

    public function testWithCookieSetCookie()
    {
        $this->withCookie('foo', 'bar');

        $this->assertCount(1, $this->defaultCookies);
        $this->assertSame('bar', $this->defaultCookies['foo']);
    }

    public function testWithCookiesSetsCookiesAndOverwritesPreviousValues()
    {
        $this->withCookie('foo', 'bar');
        $this->withCookies([
            'foo' => 'baz',
            'new-cookie' => 'new-value',
        ]);

        $this->assertCount(2, $this->defaultCookies);
        $this->assertSame('baz', $this->defaultCookies['foo']);
        $this->assertSame('new-value', $this->defaultCookies['new-cookie']);
    }

    public function testWithUnencryptedCookieSetCookie()
    {
        $this->withUnencryptedCookie('foo', 'bar');

        $this->assertCount(1, $this->unencryptedCookies);
        $this->assertSame('bar', $this->unencryptedCookies['foo']);
    }

    public function testWithUnencryptedCookiesSetsCookiesAndOverwritesPreviousValues()
    {
        $this->withUnencryptedCookie('foo', 'bar');
        $this->withUnencryptedCookies([
            'foo' => 'baz',
            'new-cookie' => 'new-value',
        ]);

        $this->assertCount(2, $this->unencryptedCookies);
        $this->assertSame('baz', $this->unencryptedCookies['foo']);
        $this->assertSame('new-value', $this->unencryptedCookies['new-cookie']);
    }

    public function testWithoutAndWithCredentials()
    {
        $this->encryptCookies = false;

        $this->assertSame([], $this->prepareCookiesForJsonRequest());

        $this->withCredentials();
        $this->defaultCookies = ['foo' => 'bar'];
        $this->assertSame(['foo' => 'bar'], $this->prepareCookiesForJsonRequest());
    }

    public function testFollowingRedirects()
    {
        $router = $this->app->make(Registrar::class);
        $url = $this->app->make(UrlGenerator::class);

        $router->get('from', function () use ($url) {
            return new RedirectResponse($url->to('to'));
        });

        $router->get('to', function () {
            return 'OK';
        });

        $this->followingRedirects()
            ->get('from')
            ->assertOk()
            ->assertSee('OK');
    }

    public function testFollowingRedirectsTerminatesInExpectedOrder()
    {
        $router = $this->app->make(Registrar::class);
        $url = $this->app->make(UrlGenerator::class);

        $callOrder = [];
        TerminatingMiddleware::$callback = function ($request) use (&$callOrder) {
            $callOrder[] = $request->path();
        };

        $router->get('from', function () use ($url) {
            return new RedirectResponse($url->to('to'));
        })->middleware(TerminatingMiddleware::class);

        $router->get('to', function () {
            return 'OK';
        })->middleware(TerminatingMiddleware::class);

        $this->followingRedirects()->get('from');

        $this->assertEquals(['from', 'to'], $callOrder);
    }
}

class MyMiddleware
{
    public function handle($request, $next)
    {
        return $next($request.'WithMiddleware');
    }
}

class TerminatingMiddleware
{
    public static $callback;

    public function handle($request, $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        call_user_func(static::$callback, $request, $response);
    }
}
