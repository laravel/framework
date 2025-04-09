<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Attributes\Middleware;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Request;
use Orchestra\Testbench\TestCase;

class RouteAttributeMiddlewareTest extends TestCase
{
    protected $container;
    protected $router;

    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($this->container = new Container);

        $this->router = new Router(new Dispatcher, $this->container);

        $this->container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        // Bind the router
        $this->container->instance(Registrar::class, $this->router);
        $this->container->instance('router', $this->router);

        // Bind UrlGenerator
        $routes = new RouteCollection;
        $this->container->instance('routes', $routes);

        $request = Request::create('/');
        $url = new UrlGenerator($routes, $request);
        $this->container->instance('url', $url);
        $this->container->instance(\Illuminate\Contracts\Routing\UrlGenerator::class, $url);

        // Register test middleware
        $this->router->aliasMiddleware('test.alias', function ($request, $next) {
            $GLOBALS['__middleware_executed'] = true;

            return $next($request);
        });

        // Register a test route
        $this->router->get('/test-attribute', [TestController::class, 'index']);
    }

    protected function defineRoutes($router)
    {
        $router->get('/test-attribute', [TestController::class, 'index']);
    }

    public function test_middleware_applied_from_attributes()
    {
        $GLOBALS['__middleware_executed'] = false;

        $request = Request::create('/test-attribute', 'GET');
        $response = $this->router->dispatch($request);

        $this->assertTrue($GLOBALS['__middleware_executed'], 'Middleware was not executed.');
        $this->assertEquals('Hello from controller', $response->getContent());
    }
}

#[Middleware('test.alias')]
class TestController
{
    #[Middleware('test.alias')]
    public function index()
    {
        return 'Hello from controller';
    }
}
