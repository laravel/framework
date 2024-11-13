<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class RouteDependencyInjectionTest extends TestCase
{
    public function test_it_can_resolve_multiple_interfaces_with_the_same_implementation()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->instance(Registrar::class, $router);

        $container->bind(TestDependencyInterfaceA::class, TestDependencyImplementation::class);
        $container->bind(TestDependencyInterfaceB::class, TestDependencyImplementation::class);

        $controller = m::mock(TestDependencyController::class)->makePartial();
        $controller->shouldReceive('index')
            ->once()
            ->withArgs(function ($a, $b) {
                return $a instanceof TestDependencyImplementation
                    && $b instanceof TestDependencyImplementation
                    && $a !== $b; // They should be different instances
            })
            ->andReturn([new TestDependencyImplementation, new TestDependencyImplementation]);

        $container->instance(TestDependencyController::class, $controller);

        $router->get('/test-inject-dependency-interfaces', TestDependencyController::class.'@index');
    }
}

interface TestDependencyInterfaceA {}

interface TestDependencyInterfaceB {}

class TestDependencyImplementation implements TestDependencyInterfaceA, TestDependencyInterfaceB {}

class TestDependencyController extends Controller
{
    public function index(TestDependencyInterfaceA $a, TestDependencyInterfaceB $b)
    {
        return [$a, $b];
    }
}
