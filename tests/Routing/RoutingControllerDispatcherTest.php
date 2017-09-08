<?php


namespace Illuminate\Tests\Routing;


use Illuminate\Container\Container;
use Illuminate\Routing\Controller;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RoutingControllerDispatcherTest extends TestCase {

    public function testCanResolveParametersThatInheritFromPreviousParameter()
    {
        $container = new Container();
        $container->instance(Foo::class, new Foo());

        $dispatcher = new ControllerDispatcher($container);

        $this->assertCount(3, $dispatcher->resolveMethodDependencies(['id' => 1], new ReflectionMethod(TestController::class, 'example')));

        // Alternative way to break test
//        $instance = new TestController();
//        $route = new Route(['GET'], '/', []);
//        $route->parameters = ['id' => 1];
//
//        $dispatcher->dispatch(
//            $route,
//            $instance,
//            'example'
//        );
    }
}

class TestController extends Controller{
    public function example($id, Bar $bar, Foo $foo){}
}
class Foo {}
class Bar extends Foo {}