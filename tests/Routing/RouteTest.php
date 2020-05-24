<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\SerializedClosure;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testRouteClosureIsProperlySerialized()
    {
        $closure = function () {
            return 'OK';
        };
        $route = new Route('GET', 'closure-route', $closure);
        $route->prepareForSerialization();
        $this->assertSame(SerializedClosure::toString($closure), $route->action['uses']);
    }
}
