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
