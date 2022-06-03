<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\ImplicitRouteBinding;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

include 'Enums.php';

class ImplicitRouteBindingTest extends TestCase
{
    public function test_it_can_resolve_the_implicit_string_backed_enum_route_bindings_for_the_given_route()
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

    public function test_implicit_string_backed_enum_internal_exception()
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

    public function test_it_can_resolve_the_implicit_int_backed_enum_route_bindings_for_the_given_route()
    {
        $action = ['uses' => function (AnimalBackedEnum $animal) {
            return $animal->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['animal' => 0];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertSame(0, $route->parameter('animal')->value);
    }

    public function test_implicit_backed_int_enum_internal_exception()
    {
        $action = ['uses' => function (AnimalBackedEnum $animal) {
            return $animal->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['animal' => 2];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        $this->expectException(BackedEnumCaseNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            'Case [%s] not found on Backed Enum [%s].',
            '2',
            AnimalBackedEnum::class,
        ));

        ImplicitRouteBinding::resolveForRoute($container, $route);
    }

    public function test_implicit_backed_int_enum_will_be_rejected_if_values_dont_match()
    {
        $action = ['uses' => function (AnimalBackedEnum $animal) {
            return $animal->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['animal' => ' 00001.'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        $this->expectException(BackedEnumCaseNotFoundException::class);
        $this->expectExceptionMessage(sprintf(
            'Case [%s] not found on Backed Enum [%s].',
            ' 00001.',
            AnimalBackedEnum::class,
        ));

        ImplicitRouteBinding::resolveForRoute($container, $route);
    }


    public function test_it_can_resolve_the_implicit_int_backed_enum_route_when_parameter_is_a_valid_int_string()
    {
        $action = ['uses' => function (AnimalBackedEnum $animal) {
            return $animal->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['animal' => '0'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertSame(0, $route->parameter('animal')->value);
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
