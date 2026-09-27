<?php

namespace Illuminate\Tests\Integration\Routing;

use ArrayIterator;
use Illuminate\Http\Request;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Arr;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompiledRouteCollectionTest extends TestCase
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
        unset($this->routeCollection, $this->router);

        parent::tearDown();
    }

    /**
     * @return \Illuminate\Routing\CompiledRouteCollection
     */
    protected function collection()
    {
        return $this->routeCollection->toCompiledRouteCollection($this->router, $this->app);
    }

    public function testRouteCollectionCanAddRoute()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $this->assertCount(1, $this->collection());
    }

    public function testRouteCollectionAddReturnsTheRoute()
    {
        $outputRoute = $this->collection()->add($inputRoute = $this->newRoute('GET', 'foo', [
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

        $routes = $this->collection();

        $this->assertSame('route_name', $routeIndex->getName());
        $this->assertSame('route_name', $routes->getByName('route_name')->getName());
        $this->assertEquals($routeIndex, $routes->getByName('route_name'));
    }

    public function testRouteCollectionCanRetrieveByAction()
    {
        $this->routeCollection->add($routeIndex = $this->newRoute('GET', 'foo/index', $action = [
            'uses' => 'FooController@index',
        ]));

        $route = $this->collection()->getByAction('FooController@index');

        $this->assertSame($action, Arr::except($routeIndex->getAction(), 'as'));
        $this->assertSame($action, Arr::except($route->getAction(), 'as'));
    }

    public function testCompiledAndNonCompiledUrlResolutionHasSamePrecedenceForActions()
    {
        $fixtures = dirname(__DIR__, 2).'/Routing/Fixtures';

        @unlink($fixtures.'/cache/routes-v7.php');
        $this->app->useBootstrapPath($fixtures);
        $app = (static function () use ($fixtures) {
            $refresh = true;

            return require $fixtures.'/app.php';
        })();
        $app['router']->get('/foo/{bar}', ['FooController', 'show']);
        $app['router']->get('/foo/{bar}/{baz}', ['FooController', 'show']);
        $app['router']->getRoutes()->refreshActionLookups();

        $this->assertSame('foo/{bar}', $app['router']->getRoutes()->getByAction('FooController@show')->uri);

        $this->artisan('route:cache')->assertExitCode(0);
        require $fixtures.'/cache/routes-v7.php';

        $this->assertSame('foo/{bar}', $app['router']->getRoutes()->getByAction('FooController@show')->uri);

        unlink($fixtures.'/cache/routes-v7.php');
    }

    public function testCompiledAndNonCompiledUrlResolutionHasSamePrecedenceForNames()
    {
        $this->router->get('/foo/{bar}', ['FooController', 'show'])->name('foo.show');
        $this->router->get('/foo/{bar}/{baz}', ['FooController', 'show'])->name('foo.show');
        $this->router->getRoutes()->refreshNameLookups();

        $this->assertSame('foo/{bar}', $this->router->getRoutes()->getByName('foo.show')->uri);
    }

    public function testRouteCollectionCanGetIterator()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $this->assertInstanceOf(ArrayIterator::class, $this->collection()->getIterator());
    }

    public function testRouteCollectionCanGetIteratorWhenEmpty()
    {
        $routes = $this->collection();

        $this->assertCount(0, $routes);
        $this->assertInstanceOf(ArrayIterator::class, $routes->getIterator());
    }

    public function testRouteCollectionCanGetIteratorWhenRoutesAreAdded()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $routes = $this->collection();

        $this->assertCount(1, $routes);

        $this->routeCollection->add($this->newRoute('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));

        $routes = $this->collection();

        $this->assertCount(2, $routes);

        $this->assertInstanceOf(ArrayIterator::class, $routes->getIterator());
    }

    public function testRouteCollectionCanHandleSameRoute()
    {
        $this->routeCollection->add($routeIndex = $this->newRoute('GET', 'foo/index', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        $routes = $this->collection();

        $this->assertCount(1, $routes);

        // Add exactly the same route
        $this->routeCollection->add($routeIndex);

        $routes = $this->collection();

        $this->assertCount(1, $routes);

        // Add a non-existing route
        $this->routeCollection->add($this->newRoute('GET', 'bar/show', [
            'uses' => 'BarController@show',
            'as' => 'bar_show',
        ]));

        $routes = $this->collection();

        $this->assertCount(2, $routes);
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
        $this->assertEquals($allRoutes, $this->collection()->getRoutes());
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

        $this->assertEquals($routesByName, $this->collection()->getRoutesByName());
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
        ], $this->collection()->getRoutesByMethod());
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

        $routes = $this->collection();

        // The lookups of $routeA should not be there anymore, because they are no longer valid.
        $this->assertNull($routes->getByName('routeA'));
        $this->assertNull($routes->getByAction('View@view'));
        // The lookups of $routeB are still there.
        $this->assertEquals($routeB, $routes->getByName('overwrittenRouteA'));
        $this->assertEquals($routeB, $routes->getByAction('OverwrittenView@view'));
    }

    public function testMatchingThrowsNotFoundExceptionWhenRouteIsNotFound()
    {
        $this->routeCollection->add($this->newRoute('GET', '/', ['uses' => 'FooController@index']));

        $this->expectException(NotFoundHttpException::class);

        $this->collection()->match(Request::create('/foo'));
    }

    public function testMatchingRespectsHttpsOnlyRoutes()
    {
        $this->routeCollection->add($this->newRoute('GET', 'account', ['https', 'uses' => 'FooController@index', 'as' => 'account']));

        $routes = $this->collection();

        $this->assertSame('account', $routes->match(Request::create('https://localhost/account'))->getName());

        $this->expectException(NotFoundHttpException::class);

        $routes->match(Request::create('http://localhost/account'));
    }

    public function testMatchingRespectsHttpOnlyRoutes()
    {
        $this->routeCollection->add($this->newRoute('GET', 'account', ['http', 'uses' => 'FooController@index', 'as' => 'account']));

        $routes = $this->collection();

        $this->assertSame('account', $routes->match(Request::create('http://localhost/account'))->getName());

        $this->expectException(NotFoundHttpException::class);

        $routes->match(Request::create('https://localhost/account'));
    }

    public function testMatchingThrowsMethodNotAllowedHttpExceptionWhenMethodIsNotAllowed()
    {
        $this->routeCollection->add($this->newRoute('GET', '/foo', ['uses' => 'FooController@index']));

        $this->expectException(MethodNotAllowedHttpException::class);

        $this->collection()->match(Request::create('/foo', 'POST'));
    }

    public function testMatchingThrowsExceptionWhenMethodIsNotAllowedWhileSameRouteIsAddedDynamically()
    {
        $this->routeCollection->add($this->newRoute('GET', '/', ['uses' => 'FooController@index']));

        $routes = $this->collection();

        $routes->add($this->newRoute('POST', '/', ['uses' => 'FooController@index']));

        $this->expectException(MethodNotAllowedHttpException::class);

        $routes->match(Request::create('/', 'PUT'));
    }

    public function testMatchingRouteWithSameDynamicallyAddedRouteAlwaysMatchesCachedOneFirst()
    {
        $this->routeCollection->add(
            $route = $this->newRoute('GET', '/', ['uses' => 'FooController@index', 'as' => 'foo'])
        );

        $routes = $this->collection();

        $routes->add($this->newRoute('GET', '/', ['uses' => 'FooController@index', 'as' => 'bar']));

        $this->assertSame('foo', $routes->match(Request::create('/', 'GET'))->getName());
    }

    public function testMatchingFindsRouteWithDifferentMethodDynamically()
    {
        $this->routeCollection->add($this->newRoute('GET', '/foo', ['uses' => 'FooController@index']));

        $routes = $this->collection();

        $routes->add($route = $this->newRoute('POST', '/foo', ['uses' => 'FooController@index']));

        $this->assertSame($route, $routes->match(Request::create('/foo', 'POST')));
    }

    public function testMatchingWildcardFromCompiledRoutesAlwaysTakesPrecedent()
    {
        $this->routeCollection->add(
            $route = $this->newRoute('GET', '{wildcard}', ['uses' => 'FooController@index', 'as' => 'foo'])
                ->where('wildcard', '.*')
        );

        $routes = $this->collection();

        $routes->add(
            $this->newRoute('GET', '{wildcard}', ['uses' => 'FooController@index', 'as' => 'bar'])
                ->where('wildcard', '.*')
        );

        $this->assertSame('foo', $routes->match(Request::create('/foo', 'GET'))->getName());
    }

    public function testMatchingDynamicallyAddedRoutesTakePrecedenceOverFallbackRoutes()
    {
        $this->routeCollection->add($this->fallbackRoute(['uses' => 'FooController@index']));
        $this->routeCollection->add(
            $this->newRoute('GET', '/foo/{id}', ['uses' => 'FooController@index', 'as' => 'foo'])
        );

        $routes = $this->collection();

        $routes->add($this->newRoute('GET', '/bar/{id}', ['uses' => 'FooController@index', 'as' => 'bar']));

        $this->assertSame('bar', $routes->match(Request::create('/bar/1', 'GET'))->getName());
    }

    public function testMatchingFallbackRouteCatchesAll()
    {
        $this->routeCollection->add($this->fallbackRoute(['uses' => 'FooController@index', 'as' => 'fallback']));
        $this->routeCollection->add(
            $this->newRoute('GET', '/foo/{id}', ['uses' => 'FooController@index', 'as' => 'foo'])
        );

        $routes = $this->collection();

        $routes->add($this->newRoute('GET', '/bar/{id}', ['uses' => 'FooController@index', 'as' => 'bar']));

        $this->assertSame('fallback', $routes->match(Request::create('/baz/1', 'GET'))->getName());
    }

    public function testMatchingCachedFallbackTakesPrecedenceOverDynamicFallback()
    {
        $this->routeCollection->add($this->fallbackRoute(['uses' => 'FooController@index', 'as' => 'fallback']));

        $routes = $this->collection();

        $routes->add($this->fallbackRoute(['uses' => 'FooController@index', 'as' => 'dynamic_fallback']));

        $this->assertSame('fallback', $routes->match(Request::create('/baz/1', 'GET'))->getName());
    }

    public function testMatchingCachedFallbackTakesPrecedenceOverDynamicRouteWithWrongMethod()
    {
        $this->routeCollection->add($this->fallbackRoute(['uses' => 'FooController@index', 'as' => 'fallback']));

        $routes = $this->collection();

        $routes->add($this->newRoute('POST', '/bar/{id}', ['uses' => 'FooController@index', 'as' => 'bar']));

        $this->assertSame('fallback', $routes->match(Request::create('/bar/1', 'GET'))->getName());
    }

    public function testSlashPrefixIsProperlyHandled()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo/bar', ['uses' => 'FooController@index', 'prefix' => '/']));

        $route = $this->collection()->getByAction('FooController@index');

        $this->assertSame('foo/bar', $route->uri());
    }

    public function testRouteWithoutNamespaceIsFound()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo/bar', ['controller' => '\App\FooController']));

        $route = $this->collection()->getByAction('App\FooController');

        $this->assertSame('foo/bar', $route->uri());
    }

    public function testGroupPrefixAndRoutePrefixAreProperlyHandled()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo/bar', ['uses' => 'FooController@index', 'prefix' => '{locale}'])->prefix('pre'));

        $route = $this->collection()->getByAction('FooController@index');

        $this->assertSame('pre/{locale}', $route->getPrefix());
    }

    public function testGroupGenerateNameForDuplicateRouteNamesThatEndWithDot()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo', ['uses' => 'FooController@index'])->name('foo.'));
        $this->routeCollection->add($route = $this->newRoute('GET', 'bar', ['uses' => 'BarController@index'])->name('foo.'));

        $routes = $this->collection();

        $this->assertSame('BarController@index', $routes->match(Request::create('/bar', 'GET'))->getAction()['uses']);
    }

    public function testRouteBindingsAreProperlySaved()
    {
        $this->routeCollection->add($this->newRoute('GET', 'posts/{post:slug}/show', [
            'uses' => 'FooController@index',
            'prefix' => 'profile/{user:username}',
            'as' => 'foo',
        ]));

        $route = $this->collection()->getByName('foo');

        $this->assertSame('profile/{user}/posts/{post}/show', $route->uri());
        $this->assertSame(['user' => 'username', 'post' => 'slug'], $route->bindingFields());
    }

    public function testMatchingSlashedRoutes()
    {
        $this->routeCollection->add(
            $route = $this->newRoute('GET', 'foo/bar', ['uses' => 'FooController@index', 'as' => 'foo'])
        );

        $this->assertSame('foo', $this->collection()->match(Request::create('/foo/bar/'))->getName());
    }

    public function testMatchingUriWithQuery()
    {
        $this->routeCollection->add(
            $route = $this->newRoute('GET', 'foo/bar', ['uses' => 'FooController@index', 'as' => 'foo'])
        );

        $this->assertSame('foo', $this->collection()->match(Request::create('/foo/bar/?foo=bar'))->getName());
    }

    public function testMatchingRootUri()
    {
        $this->routeCollection->add(
            $route = $this->newRoute('GET', '/', ['uses' => 'FooController@index', 'as' => 'foo'])
        );

        $this->assertSame('foo', $this->collection()->match(Request::create('http://example.com'))->getName());
    }

    public function testTrailingSlashIsTrimmedWhenMatchingCachedRoutes()
    {
        $this->routeCollection->add(
            $this->newRoute('GET', 'foo/bar', ['uses' => 'FooController@index', 'as' => 'foo'])
        );

        $request = Request::create('/foo/bar/');

        // Access to request path info before matching route
        $request->getPathInfo();

        $this->assertSame('foo', $this->collection()->match($request)->getName());
    }

    public function testRouteWithSamePathAndSameMethodButDiffDomainNameWithOptionsMethod()
    {
        $routes = [
            'foo_domain' => $this->newRoute('GET', 'same/path', [
                'uses' => 'FooController@index',
                'as' => 'foo',
                'domain' => 'foo.localhost',
            ]),
            'bar_domain' => $this->newRoute('GET', 'same/path', [
                'uses' => 'BarController@index',
                'as' => 'bar',
                'domain' => 'bar.localhost',
            ]),
            'no_domain' => $this->newRoute('GET', 'same/path', [
                'uses' => 'BarController@index',
                'as' => 'no_domain',
            ]),
        ];

        $this->routeCollection->add($routes['foo_domain']);
        $this->routeCollection->add($routes['bar_domain']);
        $this->routeCollection->add($routes['no_domain']);

        $expectedMethods = [
            'OPTIONS',
        ];

        $this->assertSame($expectedMethods, $this->collection()->match(
            Request::create('http://foo.localhost/same/path', 'OPTIONS')
        )->methods);

        $this->assertSame($expectedMethods, $this->collection()->match(
            Request::create('http://bar.localhost/same/path', 'OPTIONS')
        )->methods);

        $this->assertSame($expectedMethods, $this->collection()->match(
            Request::create('http://no.localhost/same/path', 'OPTIONS')
        )->methods);

        $this->assertEquals([
            'HEAD' => [
                'foo.localhostsame/path' => $routes['foo_domain'],
                'bar.localhostsame/path' => $routes['bar_domain'],
                'same/path' => $routes['no_domain'],
            ],
            'GET' => [
                'foo.localhostsame/path' => $routes['foo_domain'],
                'bar.localhostsame/path' => $routes['bar_domain'],
                'same/path' => $routes['no_domain'],
            ],
        ], $this->collection()->getRoutesByMethod());
    }

    public function testMethodNotAllowedUsesTheMethodsAllowedByTheCompiledMatcher()
    {
        $this->addResourceRoutes();

        $routes = $this->scanTrackingCollection();

        try {
            $routes->match(Request::create('/users', 'DELETE'));

            $this->fail('A method not allowed exception was not thrown.');
        } catch (MethodNotAllowedHttpException $e) {
            $this->assertSame('The DELETE method is not supported for route users. Supported methods: GET, HEAD, POST.', $e->getMessage());
            $this->assertSame(['Allow' => 'GET, HEAD, POST'], $e->getHeaders());
        }

        try {
            $routes->match(Request::create('/users/1/', 'POST'));

            $this->fail('A method not allowed exception was not thrown.');
        } catch (MethodNotAllowedHttpException $e) {
            $this->assertSame('The POST method is not supported for route users/1. Supported methods: GET, HEAD, PUT, PATCH, DELETE.', $e->getMessage());
            $this->assertSame(['Allow' => 'GET, HEAD, PUT, PATCH, DELETE'], $e->getHeaders());
        }

        $this->assertFalse($routes->scanned);
    }

    public function testOptionsRequestUsesTheMethodsAllowedByTheCompiledMatcher()
    {
        $this->addResourceRoutes();

        $routes = $this->scanTrackingCollection();

        $route = $routes->match(Request::create('/users/1', 'OPTIONS'));
        $response = $route->getAction('uses')();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('GET,HEAD,PUT,PATCH,DELETE', $response->headers->get('Allow'));
        $this->assertFalse($routes->scanned);
    }

    public function testMethodNotAllowedIgnoresNonStandardMethodsAllowedByTheCompiledMatcher()
    {
        $this->addResourceRoutes();
        $this->routeCollection->add($this->newRoute('PURGE', 'users/{user}', ['uses' => 'UserController@purge']));
        $this->routeCollection->add($this->newRoute('PURGE', 'cache', ['uses' => 'CacheController@purge']));

        $routes = $this->scanTrackingCollection();

        try {
            $routes->match(Request::create('/users/1', 'POST'));

            $this->fail('A method not allowed exception was not thrown.');
        } catch (MethodNotAllowedHttpException $e) {
            $this->assertSame(['Allow' => 'GET, HEAD, PUT, PATCH, DELETE'], $e->getHeaders());
        }

        $this->assertFalse($routes->scanned);

        try {
            $routes->match(Request::create('/cache', 'POST'));

            $this->fail('A not found exception was not thrown.');
        } catch (NotFoundHttpException) {
            $this->assertTrue($routes->scanned);
        }
    }

    public function testMethodNotAllowedScansRoutesWhenRoutesAreRestrictedToAScheme()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo', ['https', 'uses' => 'FooController@index']));
        $this->routeCollection->add($this->newRoute('DELETE', 'foo', ['uses' => 'FooController@destroy']));

        $routes = $this->scanTrackingCollection();

        try {
            $routes->match(Request::create('http://localhost/foo', 'POST'));

            $this->fail('A method not allowed exception was not thrown.');
        } catch (MethodNotAllowedHttpException $e) {
            $this->assertSame(['Allow' => 'DELETE'], $e->getHeaders());
        }

        $this->assertTrue($routes->scanned);
    }

    public function testMethodNotAllowedScansRoutesWhenCachedRoutesShareTheSameMethodAndUri()
    {
        $this->routeCollection->add($this->newRoute(['GET', 'DELETE'], 'foo/{id}', ['uses' => 'FooController@show', 'as' => 'numeric'])->where('id', '[0-9]+'));
        $this->routeCollection->add($this->newRoute('GET', 'foo/{id}', ['uses' => 'FooController@show', 'as' => 'alpha'])->where('id', '[a-z]+'));

        $routes = $this->scanTrackingCollection();

        try {
            $routes->match(Request::create('/foo/123', 'PUT'));

            $this->fail('A method not allowed exception was not thrown.');
        } catch (MethodNotAllowedHttpException $e) {
            $this->assertSame(['Allow' => 'DELETE'], $e->getHeaders());
        }

        $this->assertTrue($routes->scanned);
    }

    public function testMethodNotAllowedScansRoutesWhenRoutesAreAddedDynamically()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo/{id}', ['uses' => 'FooController@show']));

        $routes = $this->scanTrackingCollection();

        $routes->add($this->newRoute('GET', 'foo/{id}', ['uses' => 'FooController@show'])->where('id', '[a-z]+'));

        try {
            $routes->match(Request::create('/foo/123', 'POST'));

            $this->fail('A not found exception was not thrown.');
        } catch (NotFoundHttpException) {
            $this->assertTrue($routes->scanned);
        }
    }

    public function testMethodNotAllowedScansRoutesWhenCustomRouteValidatorsAreUsed()
    {
        $this->routeCollection->add($this->newRoute('GET', 'foo', ['uses' => 'FooController@index', 'as' => 'hidden']));
        $this->routeCollection->add($this->newRoute('DELETE', 'foo', ['uses' => 'FooController@destroy']));

        $routes = $this->scanTrackingCollection();

        Route::$validators = array_merge(Route::getValidators(), [new class implements ValidatorInterface
        {
            public function matches(Route $route, Request $request)
            {
                return $route->getName() !== 'hidden';
            }
        }]);

        try {
            $routes->match(Request::create('/foo', 'POST'));

            $this->fail('A method not allowed exception was not thrown.');
        } catch (MethodNotAllowedHttpException $e) {
            $this->assertSame(['Allow' => 'DELETE'], $e->getHeaders());
        } finally {
            Route::$validators = null;
        }

        $this->assertTrue($routes->scanned);
    }

    public function testMethodNotAllowedScansRoutesWhenThePathEndsWithAnEncodedSlash()
    {
        $this->routeCollection->add($this->newRoute('GET', 'files/{path}/{version?}', ['uses' => 'FileController@show'])->where('path', '.+'));
        $this->routeCollection->add($this->newRoute('DELETE', 'files/{id}', ['uses' => 'FileController@destroy'])->where('id', '.*'));

        $routes = $this->scanTrackingCollection();

        try {
            $routes->match(Request::create('/files/a%2F', 'POST'));

            $this->fail('A method not allowed exception was not thrown.');
        } catch (MethodNotAllowedHttpException $e) {
            $this->assertSame(['Allow' => 'GET, HEAD, DELETE'], $e->getHeaders());
        }

        $this->assertTrue($routes->scanned);
    }

    /**
     * Add resource style routes for the "users" resource to the route collection.
     *
     * @return void
     */
    protected function addResourceRoutes()
    {
        $this->routeCollection->add($this->newRoute('GET', 'users', ['uses' => 'UserController@index']));
        $this->routeCollection->add($this->newRoute('POST', 'users', ['uses' => 'UserController@store']));
        $this->routeCollection->add($this->newRoute('GET', 'users/{user}', ['uses' => 'UserController@show']));
        $this->routeCollection->add($this->newRoute(['PUT', 'PATCH'], 'users/{user}', ['uses' => 'UserController@update']));
        $this->routeCollection->add($this->newRoute('DELETE', 'users/{user}', ['uses' => 'UserController@destroy']));
    }

    /**
     * Compile the routes into a collection that tracks if the cached routes are scanned by method.
     *
     * @return \Illuminate\Routing\CompiledRouteCollection
     */
    protected function scanTrackingCollection()
    {
        ['compiled' => $compiled, 'attributes' => $attributes] = $this->routeCollection->compile();

        return (new class($compiled, $attributes) extends CompiledRouteCollection
        {
            public $scanned = false;

            public function get($method = null)
            {
                $this->scanned = true;

                return parent::get($method);
            }
        })->setRouter($this->router)->setContainer($this->app);
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

    /**
     * Create a new fallback Route object.
     *
     * @param  mixed  $action
     * @return \Illuminate\Routing\Route
     */
    protected function fallbackRoute($action)
    {
        $placeholder = 'fallbackPlaceholder';

        return $this->newRoute(
            'GET', "{{$placeholder}}", $action
        )->where($placeholder, '.*')->fallback();
    }
}
