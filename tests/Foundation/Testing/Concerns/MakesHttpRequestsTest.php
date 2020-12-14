<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Cookie\CookieJar;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Encryption\Encrypter;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Date;
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

    public function testUsingCookiesFromLastResponse()
    {
        $now = Date::now();

        $encrypter = new Encrypter(str_repeat('a', 16));
        $this->app->singleton(EncrypterContract::class, function() use ($encrypter) {
            return $encrypter;
        });

        $router = $this->app->make(Registrar::class);

        $router->get('set-cookies', function() {
            return (new Response('OK'))
                ->withCookie('unencrypted-cookie', 'unencrypted-value')
                ->withCookie('expiring-cookie', 'expiring-value', 10)
                ->withCookie('encrypted-cookie', 'encrypted-value');
        })->middleware(MyEncryptCookiesMiddleware::class);

        $router->get('forget-cookies', function() {
            return (new Response('OK'))
                ->withCookie('unencrypted-cookie', '', -2628000)
                ->withCookie('expiring-cookie', '', -2628000)
                ->withCookie('encrypted-cookie', '', -2628000);
        })->middleware(MyEncryptCookiesMiddleware::class);

        $this->get('set-cookies');

        $this->usingCookiesFromLastResponse();

        // Ensure that cookies set in the response to get('set-cookies')
        // are prepared for the next request
        $cookies = $this->prepareCookiesForRequest();
        $this->assertEquals('encrypted-value', CookieValuePrefix::remove($encrypter->decrypt($cookies['encrypted-cookie'], false)));
        $this->assertEquals('unencrypted-value', $cookies['unencrypted-cookie']);
        $this->assertEquals('expiring-value', $cookies['expiring-cookie']);

        // Time-travel past the expiration of the "expiring-cookie"
        // and ensure it won't be used for the next request
        Date::setTestNow($now->copy()->addMinutes(11));
        $cookies = $this->prepareCookiesForRequest();
        $this->assertArrayHasKey('encrypted-cookie', $cookies);
        $this->assertArrayHasKey('unencrypted-cookie', $cookies);
        $this->assertArrayNotHasKey('expiring-cookie', $cookies);

        // Ensure that cookies that are "forgotten" (i.e. set to expire one
        // month in the past) are not used in the next request
        $this->usingCookiesFromLastResponse()->get('forget-cookies');
        $cookies = $this->prepareCookiesForRequest();
        $this->assertArrayNotHasKey('encrypted-cookie', $cookies);
        $this->assertArrayNotHasKey('unencrypted-cookie', $cookies);
        $this->assertArrayNotHasKey('expiring-cookie', $cookies);
    }

    public function testWithoutAndWithCredentials()
    {
        $this->encryptCookies = false;

        $this->assertSame([], $this->prepareCookiesForJsonRequest());

        $this->withCredentials();
        $this->defaultCookies = ['foo' => 'bar'];
        $this->assertSame(['foo' => 'bar'], $this->prepareCookiesForJsonRequest());
    }
}

class MyEncryptCookiesMiddleware extends EncryptCookies
{
    protected $except = [
        'unencrypted-cookie',
        'expiring-cookie',
    ];
}

class MyMiddleware
{
    public function handle($request, $next)
    {
        return $next($request.'WithMiddleware');
    }
}
