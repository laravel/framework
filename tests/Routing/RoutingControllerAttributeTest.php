<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Routing\Controller as RoutingController;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Route;
use Override;
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

    public function testControllerMiddlewareMergesWithAttributeMiddleware()
    {
        $route = new Route('GET', 'foo', ['uses' => StaticMiddlewareController::class.'@index']);
        $route->setContainer(new Container);

        $this->assertEquals(['static-middleware', 'attribute-middleware-1', 'attribute-middleware-2'], $route->gatherMiddleware());

        $route = new Route('GET', 'bar', ['uses' => DynamicMiddlewareController::class.'@index']);
        $route->setContainer(new Container);

        $this->assertEquals(['dynamic-middleware', 'attribute-middleware-1', 'attribute-middleware-2'], $route->gatherMiddleware());
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

#[Middleware('attribute-middleware-1')]
class StaticMiddlewareController implements HasMiddleware
{
    #[Override]
    public static function middleware(): array
    {
        return ['static-middleware'];
    }

    #[Middleware('attribute-middleware-2')]
    public function index()
    {
        //
    }
}

#[Middleware('attribute-middleware-1')]
class DynamicMiddlewareController extends RoutingController
{
    public function __construct()
    {
        $this->middleware('dynamic-middleware');
    }

    #[Middleware('attribute-middleware-2')]
    public function index()
    {
        //
    }
}
