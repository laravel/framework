<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Routing\Attributes\Controllers\Middleware as MiddlewareAttribute;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

class RoutingControllerMiddlewareMergingTest extends TestCase
{
    public function testMiddlewareAttributeAndHasMiddlewareAreMerged()
    {
        $route = new Route('GET', 'foo', ['uses' => HasMiddlewareWithAttributeController::class.'@index']);
        $route->setContainer(new Container);

        $this->assertEquals(['static', 'attribute'], $route->gatherMiddleware());
    }

    public function testMiddlewareAttributeAndLegacyMiddlewareAreMerged()
    {
        $route = new Route('GET', 'foo', ['uses' => LegacyMiddlewareWithAttributeController::class.'@index']);
        $route->setContainer(new Container);

        $this->assertEquals(['legacy', 'attribute'], $route->gatherMiddleware());
    }
}

class HasMiddlewareWithAttributeController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('static')];
    }

    #[MiddlewareAttribute('attribute')]
    public function index()
    {
    }
}

class LegacyMiddlewareWithAttributeController extends \Illuminate\Routing\Controller
{
    public function __construct()
    {
        $this->middleware('legacy');
    }

    #[MiddlewareAttribute('attribute')]
    public function index()
    {
    }
}
