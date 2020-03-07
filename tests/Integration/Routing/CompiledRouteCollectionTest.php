<?php

namespace Illuminate\Tests\Routing;

use ArrayIterator;
use Illuminate\Http\Request;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Tests\Integration\IntegrationTest;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompiledRouteCollectionTest extends IntegrationTest
{
    /**
     * @var \Illuminate\Routing\CompiledRouteCollection
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

        $this->routeCollection = (new CompiledRouteCollection([], []))
            ->setRouter($this->router)
            ->setContainer($this->app);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->routeCollection);
        unset($this->router);
    }

    public function testRouteCollectionCanAddRoute()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));
        $this->assertCount(1, $this->routeCollection);
    }

    public function testRouteCollectionAddReturnsTheRoute()
    {
        $outputRoute = $this->routeCollection->add($inputRoute = $this->newRoute('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));
        $this->assertInstanceOf(Route::class, $outputRoute);
        $this->assertEquals($inputRoute, $outputRoute);
    }

    public function testRouteCollectionCanRetrieveByName()
    {
        $this->routeCollection->add($routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'route_name',
        ]));

        $this->assertSame('route_name', $routeIndex->getName());
        $this->assertSame('route_name', $this->routeCollection->getByName('route_name')->getName());
        $this->assertEquals($routeIndex, $this->routeCollection->getByName('route_name'));
    }

    public function testRouteCollectionCanRetrieveByAction()
    {
        $this->routeCollection->add($routeIndex = $this->newRoute('GET', 'foo/index', $action = [
            'uses' => 'FooController@index',
        ]));

        $route = $this->routeCollection->getByAction('FooController@index');

        $this->assertSame($action, Arr::except($routeIndex->getAction(), 'as'));
        $this->assertSame($action, Arr::except($route->getAction(), 'as'));
    }

    public function testRouteCollectionCanGetIterator()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));
        $this->assertInstanceOf(ArrayIterator::class, $this->routeCollection->getIterator());
    }

    public function testRouteCollectionCanGetIteratorWhenEmpty()
    {
        $this->assertCount(0, $this->routeCollection);
        $this->assertInstanceOf(ArrayIterator::class, $this->routeCollection->getIterator());
    }

    public function testRouteCollectionCanGetIteratorWhenRouteAreAdded()
    {
        $this->routeCollection->add($routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));
        $this->assertCount(1, $this->routeCollection);

        $this->routeCollection->add($routeShow = $this->newRoute('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));
        $this->assertCount(2, $this->routeCollection);

        $this->assertInstanceOf(ArrayIterator::class, $this->routeCollection->getIterator());
    }

    public function testRouteCollectionCanHandleSameRoute()
    {
        $routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]);

        $this->routeCollection->add($routeIndex);
        $this->assertCount(1, $this->routeCollection);

        // Add exactly the same route
        $this->routeCollection->add($routeIndex);
        $this->assertCount(1, $this->routeCollection);

        // Add a non-existing route
        $this->routeCollection->add($this->newRoute('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));
        $this->assertCount(2, $this->routeCollection);
    }

    public function testRouteCollectionCanGetAllRoutes()
    {
        $this->routeCollection->add($routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $this->routeCollection->add($routeShow = $this->newRoute('GET', 'foo/show', [
            'uses' => 'FooController@show',
            'as' => 'foo_show',
        ]));

        $this->routeCollection->add($routeNew = $this->newRoute('POST', 'bar', [
            'uses' => 'BarController@create',
            'as' => 'bar_create',
        ]));

        $allRoutes = [
            $routeIndex,
            $routeShow,
            $routeNew,
        ];
        $this->assertEquals($allRoutes, $this->routeCollection->getRoutes());
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

        $this->routeCollection->add($routesByName['foo_index']);
        $this->routeCollection->add($routesByName['foo_show']);
        $this->routeCollection->add($routesByName['bar_create']);

        $this->assertEquals($routesByName, $this->routeCollection->getRoutesByName());
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

        $this->routeCollection->add($routes['foo_index']);
        $this->routeCollection->add($routes['foo_show']);
        $this->routeCollection->add($routes['bar_create']);

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
        ], $this->routeCollection->getRoutesByMethod());
    }

    public function testRouteCollectionCleansUpOverwrittenRoutes()
    {
        // Create two routes with the same path and method.
        $routeA = $this->newRoute('GET', 'product', ['controller' => 'View@view', 'as' => 'routeA']);
        $routeB = $this->newRoute('GET', 'product', ['controller' => 'OverwrittenView@view', 'as' => 'overwrittenRouteA']);

        $this->routeCollection->add($routeA);
        $this->routeCollection->add($routeB);

        // Check if the lookups of $routeA and $routeB are there.
        $this->assertEquals($routeA, $this->routeCollection->getByName('routeA'));
        $this->assertEquals($routeA, $this->routeCollection->getByAction('View@view'));
        $this->assertEquals($routeB, $this->routeCollection->getByName('overwrittenRouteA'));
        $this->assertEquals($routeB, $this->routeCollection->getByAction('OverwrittenView@view'));

        // The lookups of $routeB are still there.
        $this->assertEquals($routeB, $this->routeCollection->getByName('overwrittenRouteA'));
        $this->assertEquals($routeB, $this->routeCollection->getByAction('OverwrittenView@view'));
    }

    public function testMatchingThrowsNotFoundExceptionWhenRouteIsNotFound()
    {
        $this->routeCollection->add($this->newRoute('GET', '/', ['uses' => 'FooController@index']));

        $this->expectException(NotFoundHttpException::class);

        $this->routeCollection->match(Request::create('/foo'));
    }

    public function testMatchingThrowsMethodNotAllowedHttpExceptionWhenMethodIsNotAllowed()
    {
        $this->routeCollection->add($this->newRoute('POST', '/foo', ['uses' => 'FooController@index']));

        $this->expectException(MethodNotAllowedHttpException::class);

        $this->routeCollection->match(Request::create('/foo'));
    }

    public function testSlashPrefixIsProperly()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo/bar', ['uses' => 'FooController@index', 'prefix' => '/']));

        $route = $this->routeCollection->getByAction('FooController@index');

        $this->assertEquals('foo/bar', $route->uri());
    }

    public function testRouteBindingsAreProperlySaved()
    {
        $this->routeCollection->add($this->newRoute('GET', 'posts/{post:slug}/show', [
            'uses' => 'FooController@index',
            'prefix' => 'profile/{user:username}',
            'as' => 'foo',
        ]));

        $route = $this->routeCollection->getByName('foo');

        $this->assertEquals('profile/{user}/posts/{post}/show', $route->uri());
        $this->assertSame(['user' => 'username', 'post' => 'slug'], $route->bindingFields());
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
