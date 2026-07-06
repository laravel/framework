<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use UnitEnum;

class MakesHttpRequestsTest extends TestCase
{
    public function testFromSetsHeaderAndSession()
    {
        $this->from('previous/url');

        $this->assertSame('previous/url', $this->defaultHeaders['referer']);
        $this->assertSame('previous/url', $this->app['session']->previousUrl());
    }

    public function testFromRouteSetsHeaderAndSession()
    {
        $router = $this->app->make(Registrar::class);

        $router->get('previous/url', fn () => 'ok')->name('previous-url');

        $this->fromRoute('previous-url');

        $this->assertSame('http://localhost/previous/url', $this->defaultHeaders['referer']);
        $this->assertSame('http://localhost/previous/url', $this->app['session']->previousUrl());
    }

    public function testFromRemoveHeader()
    {
        $this->withHeader('name', 'Milwad')->from('previous/url');

        $this->assertSame('Milwad', $this->defaultHeaders['name']);

        $this->withoutHeader('name')->from('previous/url');

        $this->assertArrayNotHasKey('name', $this->defaultHeaders);
    }

    public function testFromRemoveHeaders()
    {
        $this->withHeaders([
            'name' => 'Milwad',
            'foo' => 'bar',
        ])->from('previous/url');

        $this->assertSame('Milwad', $this->defaultHeaders['name']);
        $this->assertSame('bar', $this->defaultHeaders['foo']);

        $this->withoutHeaders(['name', 'foo'])->from('previous/url');

        $this->assertArrayNotHasKey('name', $this->defaultHeaders);
        $this->assertArrayNotHasKey('foo', $this->defaultHeaders);
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

    public function testWithPrecognition()
    {
        $this->withPrecognition();
        $this->assertSame('true', $this->defaultHeaders['Precognition']);

        $this->app->make(Registrar::class)
            ->get('test-route', fn () => 'ok')->middleware(HandlePrecognitiveRequests::class);
        $this->get('test-route')
            ->assertStatus(204)
            ->assertHeader('Precognition', 'true')
            ->assertHeader('Precognition-Success', 'true');
    }

    #[DataProvider('providesCallRouteExceptions')]
    public function testCallRouteFails(string|UnitEnum $name, ?string $method, \Throwable $exception)
    {
        $this->expectExceptionObject($exception);

        $router = $this->app['router']->match(['PUT', 'PATCH'], '/', fn () => '')->name('test');
        $this->callRoute($name, method: $method);
    }

    /**
     * @return array<string, array{string|BackedEnum, ?string, \Throwable}>
     */
    public static function providesCallRouteExceptions(): array
    {
        return [
            'integer-backed enum' => [RouteNumbers::Foo, null, new InvalidArgumentException('Attribute [name] expects a string backed enum.')],
            'non-existing route' => ['foo', null, new RouteNotFoundException("Route [{$name}] not defined.")],
            'ambigious HTTP method' => ['test', null, new InvalidArgumentException('This route supports multiple HTTP methods. Please provide one of: PUT, PATCH')],
            'wrong HTTP method' => ['test', 'DELETE', new InvalidArgumentException('HTTP method [DELETE] not support by this route. Please provide one of: PUT, PATCH')],
        ];
    }

    #[DataProvider('providesCallRouteOptions')]
    public function testCallRouteSucceeds(string|UnitEnum $name, ?string $method)
    {
        $this->app['router']->get('/', fn () => 'tada!')->name('foo');
        $this->app['router']->match(['PUT', 'PATCH'], '/', fn () => 'tada!')->name('bar');
        $this->app['router']->delete('/', fn () => 'tada!')->name('baz');
        $response = $this->callRoute($name, method: $method);
        $response->assertOk();
        $response->assertSeeText('tada!');
    }

    /**
     * @return array<string, array{string|BackedEnum, ?string}>
     */
    public static function providesCallRouteOptions(): array
    {
        return [
            'unambigious HTTP method' => ['baz', null],
            'GET/HEAD HTTP method fallback' => [RouteNames::Foo, null],
            'provide HTTP method for ambigious route' => [RouteNames::Bar, 'PUT'],
            'provide HTTP method despite being unambigious' => ['baz', 'DELETE'],
        ];
    }
}

enum RouteNumbers: int
{
    case Foo = 1;
    case Bar = 2;
}

enum RouteNames: string
{
    case Foo = 'foo';
    case Bar = 'bar';
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
