<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

class RoutingControllerAttributeTest extends TestCase
{
    public function testControllerMiddlewareAttributesAreInherited()
    {
        $route = new Route('GET', 'foo', ['uses' => InheritMiddlewareController::class.'@index']);
        $route->setContainer(new Container);

        $this->assertEquals(['auth', 'log'], $route->gatherMiddleware());
    }

    public function testControllerMiddlewareAttributesAreInheritedInDeclarationOrder()
    {
        $route = new Route('GET', 'foo', ['uses' => InheritMiddlewareDeclarationOrderController::class.'@index']);
        $route->setContainer(new Container);

        $this->assertEquals(['middleware1', 'middleware2', 'middleware3'], $route->gatherMiddleware());
    }
}

abstract class Controller
{
    //
}

#[Middleware('auth')]
abstract class BaseMiddlewareController extends Controller
{
    //
}

#[Middleware('log')]
class InheritMiddlewareController extends BaseMiddlewareController
{
    public function index()
    {
        //
    }
}

#[Middleware('middleware1')]
#[Middleware('middleware2')]
abstract class BaseMiddlewareDeclarationOrderController extends Controller
{
    //
}

#[Middleware('middleware3')]
class InheritMiddlewareDeclarationOrderController extends BaseMiddlewareDeclarationOrderController
{
    public function index()
    {
        //
    }
}
