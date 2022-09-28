<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\ImplicitRouteBinding;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

if (PHP_VERSION_ID >= 80100) {
    include 'Enums.php';
}

class ImplicitRouteBindingTest extends TestCase
{
    /**
     * @requires PHP >= 8.1
     */
    public function test_it_can_resolve_the_implicit_backed_enum_route_bindings_for_the_given_route()
    {
        $action = ['uses' => function (CategoryBackedEnum $category) {
            return $category->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['category' => 'fruits'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertSame('fruits', $route->parameter('category')->value);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function test_it_can_resolve_the_backed_enum_default_value_for_the_given_route()
    {
        $container = Container::getInstance();
        $container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        $action = ['uses' => function (CategoryBackedEnum $category = CategoryBackedEnum::Fruits) {
            return $category->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->setContainer($container);
        $route->parameters = [];

        $this->assertSame('fruits', $route->run());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function test_it_does_not_resolve_implicit_non_backed_enum_route_bindings_for_the_given_route()
    {
        $action = ['uses' => function (CategoryEnum $category) {
            return $category->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['category' => 'fruits'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertIsString($route->parameter('category'));
        $this->assertSame('fruits', $route->parameter('category'));
    }

    /**
     * @requires PHP >= 8.1
     */
    public function test_implicit_backed_enum_internal_exception()
    {
        $action = ['uses' => function (CategoryBackedEnum $category) {
            return $category->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['category' => 'cars'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        $this->expectException(BackedEnumCaseNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            'Case [%s] not found on Backed Enum [%s].',
            'cars',
            CategoryBackedEnum::class,
        ));

        ImplicitRouteBinding::resolveForRoute($container, $route);
    }

    public function test_it_can_resolve_the_implicit_model_route_bindings_for_the_given_route()
    {
        $this->expectNotToPerformAssertions();

        $action = ['uses' => function (ImplicitRouteBindingUser $user) {
            return $user;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['user' => new ImplicitRouteBindingUser];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);
    }
}

class ImplicitRouteBindingUser extends Model
{
    //
}
