<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\Sorting\RoutesSorter;

class RoutingRoutesSortingTest extends PHPUnit_Framework_TestCase
{
    public function testSortRoutesWithMandatoryParams()
    {
        $router = $this->getRouter();
        $router->any('foo/{param}', function ($param) { return 'foo/'.$param; });
        $router->any('foo/bar', function () { return 'foobarconstant'; });

        // foo/bar route is masked
        $this->assertEquals('foo/bar', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertEquals('foo/111', $router->dispatch(Request::create('foo/111', 'GET'))->getContent());

        $router->processRoutes(new RoutesSorter());

        // foo/bar route matches
        $this->assertEquals('foobarconstant', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertEquals('foo/111', $router->dispatch(Request::create('foo/111', 'GET'))->getContent());
    }

    public function testSortRoutesWithOptionalParams()
    {
        $router = $this->getRouter();
        $router->any('foo/{param?}', function ($param = 'void') { return 'foo/'.$param; });
        $router->any('foo/bar', function () { return 'foobarconstant'; });
        $router->any('foo', function () { return 'fooconstant'; });

        // foo and foo/bar routes are masked
        $this->assertEquals('foo/void', $router->dispatch(Request::create('foo', 'GET'))->getContent());
        $this->assertEquals('foo/bar', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertEquals('foo/111', $router->dispatch(Request::create('foo/111', 'GET'))->getContent());

        $router->processRoutes(new RoutesSorter());

        // foo and foo/bar route match
        $this->assertEquals('fooconstant', $router->dispatch(Request::create('foo', 'GET'))->getContent());
        $this->assertEquals('foobarconstant', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertEquals('foo/111', $router->dispatch(Request::create('foo/111', 'GET'))->getContent());
    }

    public function testSortRoutesWithMandatoryAndOptionalParams()
    {
        $router = $this->getRouter();
        $router->any('foo/{param1}/{param2?}', function ($param1, $param2 = 'void') { return 'foo/'.$param1.'/'.$param2; });
        $router->any('foo/bar/baz', function () { return 'foobarbazconstant'; });
        $router->any('foo/bar', function () { return 'foobarconstant'; });

        // foo/bar and foo/bar/baz route are masked
        $this->assertEquals('foo/bar/void', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertEquals('foo/bar/baz', $router->dispatch(Request::create('foo/bar/baz', 'GET'))->getContent());
        $this->assertEquals('foo/111/222', $router->dispatch(Request::create('foo/111/222', 'GET'))->getContent());

        $router->processRoutes(new RoutesSorter());

        // foo and foo/bar/baz route match
        $this->assertEquals('foobarconstant', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertEquals('foobarbazconstant', $router->dispatch(Request::create('foo/bar/baz', 'GET'))->getContent());
        $this->assertEquals('foo/111/222', $router->dispatch(Request::create('foo/111/222', 'GET'))->getContent());
    }

    public function testSortRoutesWithPrefixes()
    {
        $router = $this->getRouter();
        $router->group(['prefix' => 'prefix'], function (Router $router) {
            $router->any('foo/{param}', function ($param) { return 'foo/'.$param; });
            $router->any('foo/bar', function () { return 'foobarconstant'; });
        });

        // foo/bar route is masked
        $this->assertEquals('foo/bar', $router->dispatch(Request::create('prefix/foo/bar', 'GET'))->getContent());
        $this->assertEquals('foo/111', $router->dispatch(Request::create('prefix/foo/111', 'GET'))->getContent());

        $router->processRoutes(new RoutesSorter());

        // foo/bar route matches
        $this->assertEquals('foobarconstant', $router->dispatch(Request::create('prefix/foo/bar', 'GET'))->getContent());
        $this->assertEquals('foo/111', $router->dispatch(Request::create('prefix/foo/111', 'GET'))->getContent());
    }

    /**
     * @dataProvider isRouteMaskedTestCases
     */
    public function testIsRouteMasked(Route $route1, Route $route2, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->checkIfRouteIsMasked($route1, $route2));
    }

    private function checkIfRouteIsMasked(Route $route1, Route $route2)
    {
        $method = new ReflectionMethod(RoutesSorter::class, 'isRouteMasked');
        $method->setAccessible(true);

        return $method->invoke($this->getRoutesSorter(), $route1, $route2);
    }

    public function isRouteMaskedTestCases()
    {
        $ret = [];

        // uris without the same prefix are not shadowed

        $router = $this->getRouter();
        $route1 = $router->any('foo/{param}', function () {});
        $route2 = $router->any('bar/{param}', function () {});
        $ret[0] = [$route1, $route2, false];

        // mandatory params do not shadow when segment not present

        $router = $this->getRouter();
        $routes[] = $router->any('foo/{param}', function () {});
        $routes[] = $router->any('foo', function () {});
        $ret[1] = [$route1, $route2, false];

        $router = $this->getRouter();
        $route1 = $router->any('foo', function () {});
        $route2 = $router->any('foo/{param}', function () {});
        $ret[2] = [$route1, $route2, false];

        $router = $this->getRouter();
        $route1 = $router->any('foo/{param1}/{param2}', function () {});
        $route2 = $router->any('foo/bar', function () {});
        $ret[3] = [$route1, $route2, false];

        $router = $this->getRouter();
        $route1 = $router->any('foo/bar', function () {});
        $route2 = $router->any('foo/{param1}/{param2}', function () {});
        $ret[4] = [$route1, $route2, false];

        // mandatory params shadow constant segments

        $router = $this->getRouter();
        $route1 = $router->any('foo/bar', function () {});
        $route2 = $router->any('foo/{param}', function () {});
        $ret[5] = [$route1, $route2, true];

        $router = $this->getRouter();
        $route1 = $router->any('foo/{param}', function () {});
        $route2 = $router->any('foo/bar', function () {});
        $ret[6] = [$route1, $route2, false];

        // optional params shadow constant segments

        $router = $this->getRouter();
        $route1 = $router->any('foo/bar', function () {});
        $route2 = $router->any('foo/{param?}', function () {});
        $ret[7] = [$route1, $route2, true];

        $router = $this->getRouter();
        $route1 = $router->any('foo/{param?}', function () {});
        $route2 = $router->any('foo/bar', function () {});
        $ret[8] = [$route1, $route2, false];

        // check shadowing when mandatory and optional params present

        $router = $this->getRouter();
        $route1 = $router->any('foo/bar/baz', function () {});
        $route2 = $router->any('foo/{param1}/{param2?}', function () {});
        $ret[9] = [$route1, $route2, true];

        $router = $this->getRouter();
        $route1 = $router->any('foo/{param1}/{param2?}', function () {});
        $route2 = $router->any('foo/bar/baz', function () {});
        $ret[10] = [$route1, $route2, false];

        // optional params shadow not present segments

        $router = $this->getRouter();
        $route1 = $router->any('foo', function () {});
        $route2 = $router->any('foo/{param?}', function () {});
        $ret[11] = [$route1, $route2, true];

        $router = $this->getRouter();
        $route1 = $router->any('foo/{param?}', function () {});
        $route2 = $router->any('foo', function () {});
        $ret[12] = [$route1, $route2, false];

        $router = $this->getRouter();
        $route1 = $router->any('foo/{param1}', function () {});
        $route2 = $router->any('foo/{param1}/{param2?}', function () {});
        $ret[13] = [$route1, $route2, true];

        $router = $this->getRouter();
        $route1 = $router->any('foo/{param1}/{param2?}', function () {});
        $route2 = $router->any('foo/{param1}', function () {});
        $ret[14] = [$route1, $route2, false];

        return $ret;
    }

    /**
     * @return RoutesSorter
     */
    private function getRoutesSorter()
    {
        return new RoutesSorter();
    }

    /**
     * @return Router
     */
    private function getRouter()
    {
        return new Router(new Illuminate\Events\Dispatcher());
    }
}
