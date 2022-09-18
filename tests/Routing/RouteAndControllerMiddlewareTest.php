<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouteAndControllerMiddlewareTest extends TestCase
{
    public function testControllerMiddlewareIsRanAfterRouteMiddleware()
    {
        $counter = 0;

        $container = new Container;
        $router = $this->getRouter($container);
        $router->middlewarePriority = [ControllerMiddleware::class, RouteMiddleware::class];

        $container->when(RouteMiddleware::class)
            ->needs('$callback')
            ->give(function () use (&$counter) {
                return function () use (&$counter) {
                    $counter++;
                    $this->assertSame(1, $counter);
                };
            });

        $container->when(ControllerMiddleware::class)
            ->needs('$callback')
            ->give(function () use (&$counter) {
                return function () use (&$counter) {
                    $counter++;
                    $this->assertSame(2, $counter);
                };
            });

        $route = $router->get('/', TestController::class);
        $route->middleware(RouteMiddleware::class);

        $router->dispatch(Request::create('/', 'GET'));
    }

    public function testMiddlewareRunsOnce()
    {
        $counter = 0;

        $container = new Container;
        $router = $this->getRouter($container);

        $container->when(ControllerMiddleware::class)
            ->needs('$callback')
            ->give(function () use (&$counter) {
                return function () use (&$counter) {
                    $counter++;
                };
            });

        $route = $router->get('/', TestController::class);
        $route->middleware(ControllerMiddleware::class);

        $router->dispatch(Request::create('/', 'GET'));
        $this->assertSame(1, $counter);
    }

    public function testRouteMiddlewareAffectsControllerDependencies()
    {
        $container = new Container;
        $router = $this->getRouter($container);

        $container->when(RouteMiddleware::class)
            ->needs('$callback')
            ->give(function ($container) {
                return function () use ($container) {
                    $container->when(Dependency::class)
                        ->needs('$value')
                        ->give('bar');
                };
            });

        $container->when(ControllerMiddleware::class)
            ->needs('$callback')
            ->give(function ($container) {
                return function () use ($container) {
                    $container->when(Dependency::class)
                        ->needs('$value')
                        ->give('qux');
                };
            });

        $route = $router->get('/', TestController::class);
        $route->middleware(RouteMiddleware::class);

        $response = $router->dispatch(Request::create('/', 'GET'));
        $this->assertSame('bar', $response->getContent());
    }

    public function testControllerIsRecreatedOnEveryDispatch()
    {
        $container = new Container;
        $router = $this->getRouter($container);

        $container->when(Dependency::class)
            ->needs('$value')
            ->give(function () use (&$counter) {
                $counter++;

                return $counter;
            });

        $container->when(ControllerMiddleware::class)
            ->needs('$callback')
            ->give(function () {
                return function () {
                };
            });

        $router->get('/', TestController::class);

        $response = $router->dispatch(Request::create('/', 'GET'));
        $this->assertSame('1', $response->getContent());

        $response = $router->dispatch(Request::create('/', 'GET'));
        $this->assertSame('2', $response->getContent());
    }

    public function testControllerIsTheSameInstanceWhileRouteIsNotDispatched()
    {
        $container = new Container;
        $router = $this->getRouter($container);
        $route = $router->get('/', TestController::class);

        $this->assertSame($route->getController(), $route->getController());
    }

    protected function getRouter(Container $container)
    {
        $router = new Router(new Dispatcher, $container);

        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });

        return $router;
    }
}

class TestController extends Controller
{
    public function __construct(
        public readonly Dependency $dependency
    ) {
        $this->middleware(ControllerMiddleware::class);
    }

    public function __invoke()
    {
        return $this->dependency->value;
    }
}

class Dependency
{
    public function __construct(
        public readonly string $value = 'foo'
    ) {
    }
}

class RouteMiddleware
{
    public function __construct(
        public $callback
    ) {
    }

    public function handle($request, $next) {
        call_user_func($this->callback);

        return $next($request);
    }
}

class ControllerMiddleware
{
    public function __construct(
        public $callback
    ) {
    }

    public function handle($request, $next) {
        call_user_func($this->callback);

        return $next($request);
    }
}
