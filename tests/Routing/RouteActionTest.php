<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\RouteAction;
use Illuminate\Routing\Router;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\TestCase;

class RouteActionTest extends TestCase
{
    public function test_it_can_detect_a_serialized_closure()
    {
        $callable = function (RouteActionUser $user) {
            return $user;
        };

        $action = ['uses' => serialize(
            new SerializableClosure($callable)
        )];

        $this->assertTrue(RouteAction::containsSerializedClosure($action));

        $action = ['uses' => 'FooController@index'];

        $this->assertFalse(RouteAction::containsSerializedClosure($action));
    }

    public function test_action_name_is_a_string()
    {
        $router = new Router(new Dispatcher());

        $route = $router->get('/', ['FooController', 'index']);

        $this->assertSame('FooController@index', $route->getActionName());

        $route = $router->get('/', function () {
        });

        $this->assertSame('Closure', $route->getActionName());

        $route = $router->get('/')->uses(['FooController', 'index']);

        $this->assertSame('FooController@index', $route->getActionName());

        $route = $router->get('/')->uses(function () {
        });

        $this->assertSame('Closure', $route->getActionName());
    }
}

class RouteActionUser extends Model
{
    //
}
