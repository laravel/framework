<?php

namespace Illuminate\Tests\Routing;

use ArrayIterator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouteCollectionTest extends TestCase
{
    /**
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $routeCollection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeCollection = new RouteCollection;
    }

    public function testRouteCollectionCanAddRoute()
    {
        $this->routeCollection->add(new Route('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));
        $this->assertCount(1, $this->routeCollection);
    }

    public function testRouteCollectionAddReturnsTheRoute()
    {
        $outputRoute = $this->routeCollection->add($inputRoute = new Route('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));
        $this->assertInstanceOf(Route::class, $outputRoute);
        $this->assertEquals($inputRoute, $outputRoute);
    }

    public function testRouteCollectionCanRetrieveByName()
    {
        $this->routeCollection->add($routeIndex = new Route('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'route_name',
        ]));

        $this->assertSame('route_name', $routeIndex->getName());
        $this->assertSame('route_name', $this->routeCollection->getByName('route_name')->getName());
        $this->assertEquals($routeIndex, $this->routeCollection->getByName('route_name'));
    }

    public function testRouteCollectionCanRetrieveByAction()
    {
        $this->routeCollection->add($routeIndex = new Route('GET', 'foo/index', $action = [
            'uses' => 'FooController@index',
            'as' => 'route_name',
        ]));

        $this->assertSame($action, $routeIndex->getAction());
    }

    public function testRouteCollectionCanRetrieveByMethod()
    {
        $this->routeCollection->add($routeIndex = new Route('GET', 'foo/index', $action = [
            'uses' => 'FooController@index',
            'as' => 'route_name',
        ]));

        $this->assertCount(1, $this->routeCollection->get('GET'));
        $this->assertCount(0, $this->routeCollection->get('GET.foo/index'));
        $this->assertSame($routeIndex, $this->routeCollection->get('GET')['foo/index']);

        $this->routeCollection->add($routeShow = new Route('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));
        $this->assertCount(2, $this->routeCollection->get('GET'));
    }

    public function testRouteCollectionCanGetIterator()
    {
        $this->routeCollection->add(new Route('GET', 'foo/index', [
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
        $this->routeCollection->add($routeIndex = new Route('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));
        $this->assertCount(1, $this->routeCollection);

        $this->routeCollection->add($routeShow = new Route('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));
        $this->assertCount(2, $this->routeCollection);

        $this->assertInstanceOf(ArrayIterator::class, $this->routeCollection->getIterator());
    }

    public function testRouteCollectionCanHandleSameRoute()
    {
        $routeIndex = new Route('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]);

        $this->routeCollection->add($routeIndex);
        $this->assertCount(1, $this->routeCollection);

        // Add exactly the same route
        $this->routeCollection->add($routeIndex);
        $this->assertCount(1, $this->routeCollection);

        // Add a non-existing route
        $this->routeCollection->add(new Route('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));
        $this->assertCount(2, $this->routeCollection);
    }

    public function testRouteCollectionCanRefreshNameLookups()
    {
        $routeIndex = new Route('GET', 'foo/index', [
            'uses' => 'FooController@index',
        ]);

        // The name of the route is not yet set. It will be while adding if to the RouteCollection.
        $this->assertNull($routeIndex->getName());

        // The route name is set by calling \Illuminate\Routing\Route::name()
        $this->routeCollection->add($routeIndex)->name('route_name');

        // No route is found. This is normal, as no refresh as been done.
        $this->assertNull($this->routeCollection->getByName('route_name'));

        // After the refresh, the name will be properly set to the route.
        $this->routeCollection->refreshNameLookups();
        $this->assertEquals($routeIndex, $this->routeCollection->getByName('route_name'));
    }

    public function testRouteCollectionCanGetAllRoutes()
    {
        $this->routeCollection->add($routeIndex = new Route('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $this->routeCollection->add($routeShow = new Route('GET', 'foo/show', [
            'uses' => 'FooController@show',
            'as' => 'foo_show',
        ]));

        $this->routeCollection->add($routeNew = new Route('POST', 'bar', [
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
            'foo_index' => new Route('GET', 'foo/index', [
                'uses' => 'FooController@index',
                'as' => 'foo_index',
            ]),
            'foo_show' => new Route('GET', 'foo/show', [
                'uses' => 'FooController@show',
                'as' => 'foo_show',
            ]),
            'bar_create' => new Route('POST', 'bar', [
                'uses' => 'BarController@create',
                'as' => 'bar_create',
            ]),
        ];

        $this->routeCollection->add($routesByName['foo_index']);
        $this->routeCollection->add($routesByName['foo_show']);
        $this->routeCollection->add($routesByName['bar_create']);

        $this->assertSame($routesByName, $this->routeCollection->getRoutesByName());
    }

    public function testRouteCollectionCanGetRoutesByMethod()
    {
        $routes = [
            'foo_index' => new Route('GET', 'foo/index', [
                'uses' => 'FooController@index',
                'as' => 'foo_index',
            ]),
            'foo_show' => new Route('GET', 'foo/show', [
                'uses' => 'FooController@show',
                'as' => 'foo_show',
            ]),
            'bar_create' => new Route('POST', 'bar', [
                'uses' => 'BarController@create',
                'as' => 'bar_create',
            ]),
        ];

        $this->routeCollection->add($routes['foo_index']);
        $this->routeCollection->add($routes['foo_show']);
        $this->routeCollection->add($routes['bar_create']);

        $this->assertSame([
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
        $routeA = new Route('GET', 'product', ['controller' => 'View@view', 'as' => 'routeA']);
        $routeB = new Route('GET', 'product', ['controller' => 'OverwrittenView@view', 'as' => 'overwrittenRouteA']);

        $this->routeCollection->add($routeA);
        $this->routeCollection->add($routeB);

        // Check if the lookups of $routeA and $routeB are there.
        $this->assertEquals($routeA, $this->routeCollection->getByName('routeA'));
        $this->assertEquals($routeA, $this->routeCollection->getByAction('View@view'));
        $this->assertEquals($routeB, $this->routeCollection->getByName('overwrittenRouteA'));
        $this->assertEquals($routeB, $this->routeCollection->getByAction('OverwrittenView@view'));

        // Rebuild the lookup arrays.
        $this->routeCollection->refreshNameLookups();
        $this->routeCollection->refreshActionLookups();

        // The lookups of $routeA should not be there anymore, because they are no longer valid.
        $this->assertNull($this->routeCollection->getByName('routeA'));
        $this->assertNull($this->routeCollection->getByAction('View@view'));
        // The lookups of $routeB are still there.
        $this->assertEquals($routeB, $this->routeCollection->getByName('overwrittenRouteA'));
        $this->assertEquals($routeB, $this->routeCollection->getByAction('OverwrittenView@view'));
    }

    public function testCannotCacheDuplicateRouteNames()
    {
        $this->routeCollection->add(
            new Route('GET', 'users', ['uses' => 'UsersController@index', 'as' => 'users'])
        );
        $this->routeCollection->add(
            new Route('GET', 'users/{user}', ['uses' => 'UsersController@show', 'as' => 'users'])
        );

        $this->expectException(LogicException::class);

        $this->routeCollection->compile();
    }

    public function testRouteCollectionDontMatchNonMatchingDoubleSlashes()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('The route foo could not be found.');

        $this->routeCollection->add(new Route('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $request = Request::create('', 'GET');
        // We have to set uri in REQUEST_URI otherwise Request uses parse_url() which trim the slashes
        $request->server->set(
            'REQUEST_URI', '//foo'
        );
        $this->routeCollection->match($request);
    }

    public function testRouteCollectionRequestMethodNotAllowed()
    {
        $this->expectException(MethodNotAllowedHttpException::class);
        $this->expectExceptionMessage('The POST method is not supported for route users. Supported methods: GET, HEAD.');

        $this->routeCollection->add(
            new Route('GET', 'users', ['uses' => 'UsersController@index', 'as' => 'users'])
        );

        $request = Request::create('users', 'POST');

        $this->routeCollection->match($request);
    }

    public function testHasNameRouteMethod()
    {
        $this->routeCollection->add(
            new Route('GET', 'users', ['uses' => 'UsersController@index', 'as' => 'users'])
        );
        $this->routeCollection->add(
            new Route('GET', 'posts/{post}', ['uses' => 'PostController@show', 'as' => 'posts'])
        );

        $this->routeCollection->add(
            new Route('GET', 'books/{book}', ['uses' => 'BookController@show'])
        );

        $this->assertTrue($this->routeCollection->hasNamedRoute('users'));
        $this->assertTrue($this->routeCollection->hasNamedRoute('posts'));
        $this->assertFalse($this->routeCollection->hasNamedRoute('article'));
        $this->assertFalse($this->routeCollection->hasNamedRoute('books'));
    }

    public function testToSymfonyRouteCollection()
    {
        $this->routeCollection->add(
            new Route('GET', 'users', ['uses' => 'UsersController@index', 'as' => 'users'])
        );

        $this->assertInstanceOf("\Symfony\Component\Routing\RouteCollection", $this->routeCollection->toSymfonyRouteCollection());
    }

    public function testOverlappingRoutesMatchesFirstRoute()
    {
        $this->routeCollection->add(
            new Route('GET', 'users/{id}/{other}', ['uses' => 'UsersController@other', 'as' => 'first'])
        );

        $this->routeCollection->add(
            new Route('GET', 'users/{id}/show', ['uses' => 'UsersController@show', 'as' => 'second'])
        );

        $request = Request::create('users/1/show', 'GET');

        $this->assertCount(2, $this->routeCollection->getRoutes());
        $this->assertEquals('first', $this->routeCollection->match($request)->getName());
    }
}
