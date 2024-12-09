<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

class RouteDependencyInjectionTest extends TestCase
{
    public function test_it_can_resolve_multiple_interfaces_with_the_same_implementation()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->instance(Registrar::class, $router);

        $container->bind(TestDependencyInterfaceA::class, TestDependencyImplementation::class);
        $container->bind(TestDependencyInterfaceB::class, TestDependencyImplementation::class);

        $controller = new TestDependencyController();
        $result = $controller->index(
            $container->make(TestDependencyInterfaceA::class),
            $container->make(TestDependencyInterfaceB::class)
        );

        $this->assertInstanceOf(TestDependencyImplementation::class, $result[0]);
        $this->assertInstanceOf(TestDependencyImplementation::class, $result[1]);
    }
}

interface TestDependencyInterfaceA
{
}

interface TestDependencyInterfaceB
{
}

class TestDependencyImplementation implements TestDependencyInterfaceA, TestDependencyInterfaceB
{
}

class TestDependencyController extends Controller
{
    public function index(TestDependencyInterfaceA $a, TestDependencyInterfaceB $b)
    {
        return [$a, $b];
    }
}
