<?php

namespace Illuminate\Tests\Routing;

use ArrayIterator;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Arr;
use Illuminate\Tests\Integration\IntegrationTest;
use LogicException;

class CompiledRouteCollectionTest extends IntegrationTest
{
    /**
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routeCollection;

    /**
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->app['router'];
        $this->routeCollection = new RouteCollection;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->routeCollection);
        unset($this->router);
    }

    public function addRoute(Route $route)
    {
        return $this->routeCollection->add($route);
    }

    public function compiledRouteCollection()
    {
        return $this->routeCollection->toCompiledRouteCollection()
            ->setRouter($this->router)
            ->setContainer($this->app);
    }

    public function testRouteCollectionCanAddRoute()
    {
        $this->addRoute($this->newRoute('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $this->assertCount(1, $this->compiledRouteCollection());
    }

    public function testRouteCollectionAddThrowsAnException()
    {
        $this->expectException(LogicException::class);

        $this->compiledRouteCollection()->add($inputRoute = $this->newRoute('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));
    }

    public function testRouteCollectionCanRetrieveByName()
    {
        $this->addRoute($routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'route_name',
        ]));

        $routes = $this->compiledRouteCollection();

        $this->assertSame('route_name', $routeIndex->getName());
        $this->assertSame('route_name', $routes->getByName('route_name')->getName());
        $this->assertEquals($routeIndex, $routes->getByName('route_name'));
    }

    public function testRouteCollectionCanRetrieveByAction()
    {
        $this->addRoute($routeIndex = $this->newRoute('GET', 'foo/index', $action = [
            'uses' => 'FooController@index',
        ]));

        $routes = $this->compiledRouteCollection();

        $this->assertSame($action, Arr::except($routeIndex->getAction(), 'as'));
        $this->assertEquals($routeIndex, $routes->getByAction('FooController@index'));
    }

    public function testRouteCollectionCanGetIterator()
    {
        $this->addRoute($this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $this->assertInstanceOf(ArrayIterator::class, $this->compiledRouteCollection()->getIterator());
    }

    public function testRouteCollectionCanGetIteratorWhenEmpty()
    {
        $routes = $this->compiledRouteCollection();

        $this->assertCount(0, $routes);
        $this->assertInstanceOf(ArrayIterator::class, $routes->getIterator());
    }

    public function testRouteCollectionCanGetIteratorWhenRouteAreAdded()
    {
        $this->addRoute($routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $routes = $this->compiledRouteCollection();

        $this->assertCount(1, $routes);

        $this->addRoute($routeShow = $this->newRoute('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));

        $routes = $this->compiledRouteCollection();

        $this->assertCount(2, $routes);

        $this->assertInstanceOf(ArrayIterator::class, $routes->getIterator());
    }

    public function testRouteCollectionCanHandleSameRoute()
    {
        $this->addRoute($routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $routes = $this->compiledRouteCollection();

        $this->assertCount(1, $routes);

        // Add exactly the same route
        $this->addRoute($routeIndex);

        $routes = $this->compiledRouteCollection();

        $this->assertCount(1, $routes);

        // Add a non-existing route
        $this->addRoute($this->newRoute('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));

        $routes = $this->compiledRouteCollection();

        $this->assertCount(2, $routes);
    }

    public function testRouteCollectionCanGetAllRoutes()
    {
        $this->addRoute($routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $this->addRoute($routeShow = $this->newRoute('GET', 'foo/show', [
            'uses' => 'FooController@show',
            'as' => 'foo_show',
        ]));

        $this->addRoute($routeNew = $this->newRoute('POST', 'bar', [
            'uses' => 'BarController@create',
            'as' => 'bar_create',
        ]));

        $allRoutes = [
            $routeIndex,
            $routeShow,
            $routeNew,
        ];
        $this->assertEquals($allRoutes, $this->compiledRouteCollection()->getRoutes());
    }

    public function testRouteCollectionCanGetRoutesByName()
    {
        $routesByName = [
            'foo_index' => $this->newRoute('GET', 'foo/index', [
                'uses' => 'FooController@index',
                'as' => 'foo_index',
            ]),
            'foo_show' => $this->newRoute('GET', 'foo/show', [
                'uses' => 'FooController@show',
                'as' => 'foo_show',
            ]),
            'bar_create' => $this->newRoute('POST', 'bar', [
                'uses' => 'BarController@create',
                'as' => 'bar_create',
            ]),
        ];

        $this->addRoute($routesByName['foo_index']);
        $this->addRoute($routesByName['foo_show']);
        $this->addRoute($routesByName['bar_create']);

        $this->assertEquals($routesByName, $this->compiledRouteCollection()->getRoutesByName());
    }

    public function testRouteCollectionCanGetRoutesByMethod()
    {
        $routes = [
            'foo_index' => $this->newRoute('GET', 'foo/index', [
                'uses' => 'FooController@index',
                'as' => 'foo_index',
            ]),
            'foo_show' => $this->newRoute('GET', 'foo/show', [
                'uses' => 'FooController@show',
                'as' => 'foo_show',
            ]),
            'bar_create' => $this->newRoute('POST', 'bar', [
                'uses' => 'BarController@create',
                'as' => 'bar_create',
            ]),
        ];

        $this->addRoute($routes['foo_index']);
        $this->addRoute($routes['foo_show']);
        $this->addRoute($routes['bar_create']);

        $this->assertEquals([
            'GET' => [
                'foo/index' => $routes['foo_index'],
                'foo/show' => $routes['foo_show'],
            ],
            'HEAD' => [
                'foo/index' => $routes['foo_index'],
                'foo/show' => $routes['foo_show'],
            ],
            'POST' => [
                'bar' => $routes['bar_create'],
            ],
        ], $this->compiledRouteCollection()->getRoutesByMethod());
    }

    public function testRouteCollectionCleansUpOverwrittenRoutes()
    {
        // Create two routes with the same path and method.
        $routeA = $this->newRoute('GET', 'product', ['controller' => 'View@view', 'as' => 'routeA']);
        $routeB = $this->newRoute('GET', 'product', ['controller' => 'OverwrittenView@view', 'as' => 'overwrittenRouteA']);

        $this->addRoute($routeA);
        $this->addRoute($routeB);

        $routes = $this->compiledRouteCollection();

        $this->assertNull($routes->getByName('routeA'));
        $this->assertNull($routes->getByAction('View@view'));
        $this->assertEquals($routeB, $routes->getByName('overwrittenRouteA'));
        $this->assertEquals($routeB, $routes->getByAction('OverwrittenView@view'));
    }

    /**
     * Create a new Route object.
     *
     * @param  array|string  $methods
     * @param  string  $uri
     * @param  mixed  $action
     * @return \Illuminate\Routing\Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        return (new Route($methods, $uri, $action))
            ->setRouter($this->router)
            ->setContainer($this->app);
    }
}
