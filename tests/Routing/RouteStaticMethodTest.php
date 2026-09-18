<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Middleware\CacheStaticResponse;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use PHPUnit\Framework\TestCase;

class RouteStaticMethodTest extends TestCase
{
    protected Container $container;

    protected Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($this->container = new Container);

        $this->container->instance('config', new Repository);

        $this->router = new Router(new Dispatcher($this->container), $this->container);

        $this->container->instance(Registrar::class, $this->router);
        $this->container->bind(ControllerDispatcherContract::class, fn ($app) => new ControllerDispatcher($app));
        $this->container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));
    }

    protected function tearDown(): void
    {
        Container::setInstance(new Container);

        parent::tearDown();
    }

    public function testStaticMethodAddsCacheMiddlewareAndStoresRouteOptions()
    {
        $route = $this->router->get('about', fn () => 'about')->static(
            ttl: 600,
            browserTtl: 60,
            stripCookies: ['XSRF-TOKEN'],
            vary: ['Accept-Encoding'],
        );

        $this->assertContains(CacheStaticResponse::class, $route->middleware());
        $this->assertSame([
            'ttl' => 600,
            'browser_ttl' => 60,
            'strip_cookies' => ['XSRF-TOKEN'],
            'vary' => ['Accept-Encoding'],
        ], $route->getAction('static_cache'));
    }

    public function testStaticMethodDoesNotSetRouteLevelMiddlewareExclusions()
    {
        $route = $this->router->get('about', fn () => 'about')->static();

        $this->assertSame([], $route->excludedMiddleware());
    }

    public function testStaticMethodExcludesConfiguredMiddlewareForCacheableRequests()
    {
        $this->container->make('config')->set('cache.static.strip_middleware', [
            RouteStaticConfiguredMiddleware::class,
        ]);

        $route = $this->router->get('about', [
            'middleware' => [RouteStaticConfiguredMiddleware::class, RouteStaticUnrelatedMiddleware::class],
            fn () => 'about',
        ])->static();

        $middleware = $this->router->gatherRouteMiddleware($route, Request::create('about', 'GET'));

        $this->assertNotContains(RouteStaticConfiguredMiddleware::class, $middleware);
        $this->assertContains(RouteStaticUnrelatedMiddleware::class, $middleware);
    }

    public function testCustomStripMiddlewareOverridesConfiguredMiddleware()
    {
        $this->container->make('config')->set('cache.static.strip_middleware', [
            RouteStaticConfiguredMiddleware::class,
        ]);

        $route = $this->router->get('about', [
            'middleware' => [
                RouteStaticConfiguredMiddleware::class,
                RouteStaticCustomMiddleware::class,
                RouteStaticUnrelatedMiddleware::class,
            ],
            fn () => 'about',
        ])->static(
            stripMiddleware: [RouteStaticCustomMiddleware::class],
        );

        $this->assertSame([RouteStaticCustomMiddleware::class], $route->getAction('static_cache')['strip_middleware']);

        $middleware = $this->router->gatherRouteMiddleware($route, Request::create('about', 'GET'));

        $this->assertContains(RouteStaticConfiguredMiddleware::class, $middleware);
        $this->assertNotContains(RouteStaticCustomMiddleware::class, $middleware);
        $this->assertContains(RouteStaticUnrelatedMiddleware::class, $middleware);
    }

    public function testStaticMethodExcludesMiddlewareExpandedFromRouteGroups()
    {
        $this->router->middlewareGroup('web', [
            RouteStaticStartSessionMiddleware::class,
            RouteStaticUnrelatedMiddleware::class,
        ]);

        $route = $this->router->get('about', [
            'middleware' => 'web',
            fn () => 'about',
        ])->static();

        $middleware = $this->router->gatherRouteMiddleware($route, Request::create('about', 'GET'));

        $this->assertNotContains(RouteStaticStartSessionMiddleware::class, $middleware);
        $this->assertContains(RouteStaticUnrelatedMiddleware::class, $middleware);
        $this->assertContains(CacheStaticResponse::class, $middleware);
    }

    public function testStaticMethodKeepsMiddlewareForInertiaRequests()
    {
        $this->router->middlewareGroup('web', [
            RouteStaticStartSessionMiddleware::class,
            RouteStaticUnrelatedMiddleware::class,
        ]);

        $route = $this->router->get('about', [
            'middleware' => 'web',
            fn () => 'about',
        ])->static();

        $request = Request::create('about', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $middleware = $this->router->gatherRouteMiddleware($route, $request);

        $this->assertContains(RouteStaticStartSessionMiddleware::class, $middleware);
        $this->assertContains(RouteStaticUnrelatedMiddleware::class, $middleware);
        $this->assertContains(CacheStaticResponse::class, $middleware);
    }

    public function testStaticMethodKeepsMiddlewareForUncacheableRequests()
    {
        $route = $this->router->post('about', [
            'middleware' => [RouteStaticStartSessionMiddleware::class, RouteStaticUnrelatedMiddleware::class],
            fn () => 'about',
        ])->static();

        $middleware = $this->router->gatherRouteMiddleware($route, Request::create('about', 'POST'));

        $this->assertContains(RouteStaticStartSessionMiddleware::class, $middleware);
        $this->assertContains(RouteStaticUnrelatedMiddleware::class, $middleware);
        $this->assertContains(CacheStaticResponse::class, $middleware);
    }
}

class RouteStaticConfiguredMiddleware
{
    //
}

class RouteStaticCustomMiddleware
{
    //
}

class RouteStaticStartSessionMiddleware extends StartSession
{
    //
}

class RouteStaticUnrelatedMiddleware
{
    //
}
