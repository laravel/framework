<?php

namespace Illuminate\Tests\Integration\Routing;


use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase;
use Illuminate\Routing\Controller;

class RoutingMiddlewareTest extends TestCase
{
    public function testControllerConstructedAfterMiddlewares()
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->middleware(RoutingMiddlewareTestTestingMiddleware::class)
            ->get('test', RoutingMiddlewareTestWithoutGetMiddlewareController::class);

        $this->get('test');

        $this->assertEquals('controller', $_SERVER['routing.middleware.test.last.constructed']);

        unset($_SERVER['routing.middleware.test.last.constructed']);
    }

    public function testControllerConstructedBeforeMiddlewares()
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->middleware(RoutingMiddlewareTestTestingMiddleware::class)
            ->get('test', RoutingMiddlewareTestWithGetMiddlewareController::class);

        $this->get('test');

        $this->assertEquals('middleware', $_SERVER['routing.middleware.test.last.constructed']);

        unset($_SERVER['routing.middleware.test.last.constructed']);
    }
}

class RoutingMiddlewareTestWithoutGetMiddlewareController
{
    public function __construct()
    {
        $_SERVER['routing.middleware.test.last.constructed'] = 'controller';
    }

    public function __invoke()
    {
        return new Response('ok');
    }
}

class RoutingMiddlewareTestWithGetMiddlewareController extends Controller
{
    public function __construct()
    {
        $_SERVER['routing.middleware.test.last.constructed'] = 'controller';
    }

    public function __invoke()
    {
        return new Response('ok');
    }
}

class RoutingMiddlewareTestTestingMiddleware
{


    public function handle($request, $next)
    {
        $_SERVER['routing.middleware.test.last.constructed'] = 'middleware';
        return $next($request);
    }
}
