<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Http\Response;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class RouteStaticResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RouteStaticResponseTestSessionHandler::$written = false;
    }

    public function testStaticRouteIsCacheableAndDoesNotRunSessionMiddleware()
    {
        $response = $this->get('static-page');

        $response->assertOk();
        $response->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=3600');
        $response->assertHeader('CDN-Cache-Control', 'public, max-age=3600');
        $response->assertHeader('Vary', 'Accept-Encoding, X-Inertia');

        $this->assertFalse(RouteStaticResponseTestSessionHandler::$written);
        $this->assertSame([], $response->headers->getCookies());
    }

    public function testWebRouteWithoutStaticRunsSessionMiddleware()
    {
        $response = $this->get('dynamic-page');

        $response->assertOk();

        $this->assertTrue(RouteStaticResponseTestSessionHandler::$written);
        $this->assertNotSame([], $response->headers->getCookies());
    }

    public function testInertiaRequestBypassesStaticHeaderMutation()
    {
        $response = $this->get('static-page', ['X-Inertia' => 'true']);

        $response->assertOk();
        $response->assertHeaderMissing('CDN-Cache-Control');
        $response->assertHeader('Vary', 'Accept-Encoding');

        $this->assertTrue(RouteStaticResponseTestSessionHandler::$written);
        $this->assertContains('route_cookie', array_map(
            fn ($cookie) => $cookie->getName(),
            $response->headers->getCookies(),
        ));
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', Str::random(32));
        $app['config']->set('session.driver', 'static-route-test');
        $app['config']->set('session.expire_on_close', true);

        Session::extend('static-route-test', fn () => new RouteStaticResponseTestSessionHandler);
    }

    protected function defineRoutes($router)
    {
        Route::get('static-page', function () {
            $response = new Response('static', 200, ['Vary' => 'Accept-Encoding']);
            $response->headers->setCookie(Cookie::create('route_cookie', 'value'));

            return $response;
        })->middleware('web')->static();

        Route::get('dynamic-page', fn () => 'dynamic')->middleware('web');
    }
}

class RouteStaticResponseTestSessionHandler extends NullSessionHandler
{
    public static bool $written = false;

    public function write($sessionId, $data): bool
    {
        static::$written = true;

        return true;
    }
}
