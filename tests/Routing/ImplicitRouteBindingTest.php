<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Routing\Attributes\BindRoute;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\ImplicitRouteBinding;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

class ImplicitRouteBindingTest extends TestCase
{
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

    public function test_it_can_resolve_the_implicit_backed_enum_route_bindings_for_the_given_route_with_optional_parameter()
    {
        $action = ['uses' => function (?CategoryBackedEnum $category = null) {
            return $category->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['category' => 'fruits'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertSame('fruits', $route->parameter('category')->value);
    }

    public function test_it_handles_optional_implicit_backed_enum_route_bindings_for_the_given_route_with_optional_parameter()
    {
        $action = ['uses' => function (?CategoryBackedEnum $category = null) {
            return $category->value;
        }];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['category' => null];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertNull($route->parameter('category'));
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

    public function test_it_can_resolve_the_implicit_model_route_bindings_using_bind_route_field_attribute()
    {
        ImplicitRouteBindingAttributeUser::$lastField = null;
        ImplicitRouteBindingAttributeUser::$lastValue = null;

        $action = ['uses' => function (#[BindRoute(null, 'slug')] ImplicitRouteBindingAttributeUser $user) {
            return $user;
        }];

        $route = new Route('GET', '/test/{user}', $action);
        $route->parameters = ['user' => 'laravel'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertSame('slug', ImplicitRouteBindingAttributeUser::$lastField);
        $this->assertSame('laravel', ImplicitRouteBindingAttributeUser::$lastValue);
        $this->assertInstanceOf(ImplicitRouteBindingAttributeUser::class, $route->parameter('user'));
    }

    public function test_it_can_resolve_the_implicit_model_route_bindings_using_bind_route_parameter_attribute()
    {
        ImplicitRouteBindingAttributeUser::$lastField = null;
        ImplicitRouteBindingAttributeUser::$lastValue = null;

        $action = ['uses' => function (#[BindRoute('member')] ImplicitRouteBindingAttributeUser $user) {
            return $user;
        }];

        $route = new Route('GET', '/test/{member}', $action);
        $route->parameters = ['member' => 'laravel'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertSame('laravel', ImplicitRouteBindingAttributeUser::$lastValue);
        $this->assertInstanceOf(ImplicitRouteBindingAttributeUser::class, $route->parameter('member'));
    }

    public function test_it_can_force_trashed_resolution_using_bind_route_attribute()
    {
        ImplicitRouteBindingAttributeSoftDeletableUser::$method = null;

        $action = ['uses' => function (#[BindRoute(null, null, true)] ImplicitRouteBindingAttributeSoftDeletableUser $user) {
            return $user;
        }];

        $route = new Route('GET', '/test/{user}', $action);
        $route->parameters = ['user' => '1'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertSame('resolveSoftDeletableRouteBinding', ImplicitRouteBindingAttributeSoftDeletableUser::$method);
    }

    public function test_it_can_disable_trashed_resolution_using_bind_route_attribute()
    {
        ImplicitRouteBindingAttributeSoftDeletableUser::$method = null;

        $action = ['uses' => function (#[BindRoute(null, null, false)] ImplicitRouteBindingAttributeSoftDeletableUser $user) {
            return $user;
        }];

        $route = (new Route('GET', '/test/{user}', $action))->withTrashed();
        $route->parameters = ['user' => '1'];

        $route->prepareForSerialization();

        $container = Container::getInstance();

        ImplicitRouteBinding::resolveForRoute($container, $route);

        $this->assertSame('resolveRouteBinding', ImplicitRouteBindingAttributeSoftDeletableUser::$method);
    }
}

class ImplicitRouteBindingUser extends Model
{
    //
}

class ImplicitRouteBindingAttributeUser extends Model
{
    public static $lastField;

    public static $lastValue;

    public function resolveRouteBinding($value, $field = null)
    {
        static::$lastValue = $value;
        static::$lastField = $field;

        return new self();
    }
}

class ImplicitRouteBindingAttributeSoftDeletableUser extends Model
{
    use SoftDeletes;

    public static $method;

    public function resolveRouteBinding($value, $field = null)
    {
        static::$method = 'resolveRouteBinding';

        return new self();
    }

    public function resolveSoftDeletableRouteBinding($value, $field = null)
    {
        static::$method = 'resolveSoftDeletableRouteBinding';

        return new self();
    }
}
