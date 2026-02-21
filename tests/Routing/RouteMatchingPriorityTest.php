<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;

class RouteMatchingPriorityTest extends TestCase
{
    public function test_static_route_is_prioritized_over_dynamic_route()
    {
        $router = $this->app['router'];

        $router->get('/users/{user}', function ($user) {
            return 'dynamic-'.$user;
        });

        $router->get('/users/export', function () {
            return 'static-export';
        });

        $response = $router->dispatch(Request::create('/users/export', 'GET'));

        $this->assertEquals('static-export', $response->getContent());
    }
}