<?php

namespace Illuminate\Tests\Routing;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use Illuminate\Routing\ImplicitRouteBinding;
use Illuminate\Routing\Route;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

class ImplicitRouteBindingTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
        Model::reportRouteModelBindingExceptions();

        parent::tearDown();
    }

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

    #[DataProvider('shouldReportProvider')]
    public function testItThrowsModelNotFoundExceptionOnQueryException(bool $shouldReport): void
    {
        Model::reportRouteModelBindingExceptions($shouldReport);

        $mock = m::mock(ExceptionHandler::class);

        if ($shouldReport) {
            $mock->shouldReceive('report')->once()->with(m::type(QueryException::class));
        } else {
            $mock->shouldReceive('report')->never();
        }

        $container = Container::getInstance();
        $container->instance(ExceptionHandler::class, $mock);

        $action = ['uses' => fn (ImplicitRouteBindingUserWithQueryException $user) => $user];

        $route = new Route('GET', '/test', $action);
        $route->parameters = ['user' => 'invalid-value'];
        $route->prepareForSerialization();
        $this->expectException(ModelNotFoundException::class);

        ImplicitRouteBinding::resolveForRoute($container, $route);
    }

    /** @return list<array{0: bool}> */
    public static function shouldReportProvider(): array
    {
        return [[true], [false]];
    }
}

class ImplicitRouteBindingUser extends Model
{
    //
}

class ImplicitRouteBindingUserWithQueryException extends Model
{
    public function resolveRouteBinding($value, $field = null): void
    {
        throw new QueryException('mysql', 'select * from users where id = ?', [$value], new Exception('Out of range value'));
    }
}
