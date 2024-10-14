<?php

namespace Illuminate\Tests\Routing;

use BadMethodCallException;
use FooController;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Stringable;

include_once 'Enums.php';

class RouteRegistrarTest extends TestCase
{
    /**
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = new Router(m::mock(Dispatcher::class), Container::getInstance());
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testMiddlewareFluentRegistration()
    {
        $this->router->middleware(['one', 'two'])->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['one', 'two'], $this->getRoute()->middleware());

        $this->router->middleware('three', 'four')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['three', 'four'], $this->getRoute()->middleware());

        $this->router->get('users', function () {
            return 'all-users';
        })->middleware('five', 'six');

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['five', 'six'], $this->getRoute()->middleware());

        $this->router->middleware('seven')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['seven'], $this->getRoute()->middleware());
    }

    public function testNullNamespaceIsRespected()
    {
        $this->router->middleware(['one'])->namespace(null)->get('users', function () {
            return 'all-users';
        });

        $this->assertNull($this->getRoute()->getAction()['namespace']);
    }

    public function testMiddlewareAsStringableObject()
    {
        $one = new class implements Stringable
        {
            public function __toString()
            {
                return 'one';
            }
        };

        $this->router->middleware($one)->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertSame(['one'], $this->getRoute()->middleware());
    }

    public function testMiddlewareAsStringableObjectOnRouteInstance()
    {
        $one = new class implements Stringable
        {
            public function __toString()
            {
                return 'one';
            }
        };

        $this->router->get('users', function () {
            return 'all-users';
        })->middleware($one);

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertSame(['one'], $this->getRoute()->middleware());
    }

    public function testMiddlewareAsArrayWithStringables()
    {
        $one = new class implements Stringable
        {
            public function __toString()
            {
                return 'one';
            }
        };

        $this->router->middleware([$one, 'two'])->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertSame(['one', 'two'], $this->getRoute()->middleware());
    }

    public function testWithoutMiddlewareRegistration()
    {
        $this->router->middleware(['one', 'two'])->get('users', function () {
            return 'all-users';
        })->withoutMiddleware('one');

        $this->seeResponse('all-users', Request::create('users', 'GET'));

        $this->assertEquals(['one'], $this->getRoute()->excludedMiddleware());
    }

    public function testGetRouteWithTrashed()
    {
        $route = $this->router->get('users', [RouteRegistrarControllerStub::class, 'index'])->withTrashed();

        $this->assertTrue($route->allowsTrashedBindings());
    }

    public function testResourceWithTrashed()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
            ->only(['index', 'destroy'])
            ->withTrashed([
                'index',
                'destroy',
            ]);

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertTrue($route->allowsTrashedBindings());
        }
    }

    public function testFallbackRoute()
    {
        $route = $this->router->fallback(function () {
            return 'milwad';
        });

        $this->assertTrue($route->isFallback);
    }

    public function testSetFallbackRoute()
    {
        $route = $this->router->fallback(function () {
            return 'milwad';
        });
        $route->setFallback(false);

        $this->assertFalse($route->isFallback);

        $route->setFallback(true);

        $this->assertTrue($route->isFallback);
    }

    public function testCanRegisterGetRouteWithClosureAction()
    {
        $this->router->middleware('get-middleware')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->seeMiddleware('get-middleware');
    }

    public function testCanRegisterPostRouteWithClosureAction()
    {
        $this->router->middleware('post-middleware')->post('users', function () {
            return 'saved';
        });

        $this->seeResponse('saved', Request::create('users', 'POST'));
        $this->seeMiddleware('post-middleware');
    }

    public function testCanRegisterAnyRouteWithClosureAction()
    {
        $this->router->middleware('test-middleware')->any('users', function () {
            return 'anything';
        });

        $this->seeResponse('anything', Request::create('users', 'PUT'));
        $this->seeMiddleware('test-middleware');
    }

    public function testCanRegisterMatchRouteWithClosureAction()
    {
        $this->router->middleware('match-middleware')->match(['DELETE'], 'users', function () {
            return 'deleted';
        });

        $this->seeResponse('deleted', Request::create('users', 'DELETE'));
        $this->seeMiddleware('match-middleware');
    }

    public function testCanRegisterRouteWithArrayAndClosureAction()
    {
        $this->router->middleware('patch-middleware')->patch('users', [function () {
            return 'updated';
        }]);

        $this->seeResponse('updated', Request::create('users', 'PATCH'));
        $this->seeMiddleware('patch-middleware');
    }

    public function testCanRegisterRouteWithArrayAndClosureUsesAction()
    {
        $this->router->middleware('put-middleware')->put('users', ['uses' => function () {
            return 'replaced';
        }]);

        $this->seeResponse('replaced', Request::create('users', 'PUT'));
        $this->seeMiddleware('put-middleware');
    }

    public function testCanRegisterRouteWithControllerAction()
    {
        $this->router->middleware('controller-middleware')
                     ->get('users', RouteRegistrarControllerStub::class.'@index');

        $this->seeResponse('controller', Request::create('users', 'GET'));
        $this->seeMiddleware('controller-middleware');
    }

    public function testCanRegisterRouteWithControllerActionArray()
    {
        $this->router->middleware('controller-middleware')
                     ->get('users', [RouteRegistrarControllerStub::class, 'index']);

        $this->seeResponse('controller', Request::create('users', 'GET'));
        $this->seeMiddleware('controller-middleware');
    }

    public function testCanRegisterNamespacedGroupRouteWithControllerActionArray()
    {
        $this->router->group(['namespace' => 'WhatEver'], function () {
            $this->router->middleware('controller-middleware')
                ->get('users', [RouteRegistrarControllerStub::class, 'index']);
        });

        $this->seeResponse('controller', Request::create('users', 'GET'));
        $this->seeMiddleware('controller-middleware');

        $this->router->group(['namespace' => 'WhatEver'], function () {
            $this->router->middleware('controller-middleware')
                ->get('users', ['\\'.RouteRegistrarControllerStub::class, 'index']);
        });

        $this->seeResponse('controller', Request::create('users', 'GET'));
        $this->seeMiddleware('controller-middleware');
    }

    public function testCanRegisterRouteWithArrayAndControllerAction()
    {
        $this->router->middleware('controller-middleware')->put('users', [
            'uses' => RouteRegistrarControllerStub::class.'@index',
        ]);

        $this->seeResponse('controller', Request::create('users', 'PUT'));
        $this->seeMiddleware('controller-middleware');
    }

    public function testCanRegisterGroupWithMiddleware()
    {
        $this->router->middleware('group-middleware')->group(function ($router) {
            $router->get('users', function () {
                return 'all-users';
            });
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->seeMiddleware('group-middleware');
    }

    public function testCanRegisterGroupWithoutMiddleware()
    {
        $this->router->withoutMiddleware('one')->group(function ($router) {
            $router->get('users', function () {
                return 'all-users';
            })->middleware(['one', 'two']);
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals(['one'], $this->getRoute()->excludedMiddleware());
    }

    public function testCanRegisterGroupWithStringableMiddleware()
    {
        $one = new class implements Stringable
        {
            public function __toString()
            {
                return 'one';
            }
        };

        $this->router->middleware($one)->group(function ($router) {
            $router->get('users', function () {
                return 'all-users';
            });
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->seeMiddleware('one');
    }

    public function testCanRegisterGroupWithNamespace()
    {
        $this->router->namespace('App\Http\Controllers')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertSame(
            'App\Http\Controllers\UsersController@index',
            $this->getRoute()->getAction()['uses']
        );
    }

    public function testCanRegisterGroupWithPrefix()
    {
        $this->router->prefix('api')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertSame('api/users', $this->getRoute()->uri());
    }

    public function testCanRegisterGroupWithPrefixAndWhere()
    {
        $this->router->prefix('foo/{bar}')->where(['bar' => '[0-9]+'])->group(function ($router) {
            $router->get('here', function () {
                return 'good';
            });
        });

        $this->seeResponse('good', Request::create('foo/12345/here', 'GET'));
    }

    public function testCanRegisterGroupWithNamePrefix()
    {
        $this->router->name('api.')->group(function ($router) {
            $router->get('users', 'UsersController@index')->name('users');
        });

        $this->assertSame('api.users', $this->getRoute()->getName());
    }

    public function testCanRegisterGroupWithDomain()
    {
        $this->router->domain('{account}.myapp.com')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertSame('{account}.myapp.com', $this->getRoute()->getDomain());
    }

    public function testCanRegisterGroupWithDomainAndNamePrefix()
    {
        $this->router->domain('{account}.myapp.com')->name('api.')->group(function ($router) {
            $router->get('users', 'UsersController@index')->name('users');
        });

        $this->assertSame('{account}.myapp.com', $this->getRoute()->getDomain());
        $this->assertSame('api.users', $this->getRoute()->getName());
    }

    public function testCanRegisterGroupWithController()
    {
        $this->router->controller(RouteRegistrarControllerStub::class)->group(function ($router) {
            $router->get('users', 'index');
        });

        $this->assertSame(
            RouteRegistrarControllerStub::class.'@index',
            $this->getRoute()->getAction()['uses']
        );
    }

    public function testCanOverrideGroupControllerWithStringSyntax()
    {
        $this->router->controller(RouteRegistrarControllerStub::class)->group(function ($router) {
            $router->get('users', 'UserController@index');
        });

        $this->assertSame(
            'UserController@index',
            $this->getRoute()->getAction()['uses']
        );
    }

    public function testCanOverrideGroupControllerWithClosureSyntax()
    {
        $this->router->controller(RouteRegistrarControllerStub::class)->group(function ($router) {
            $router->get('users', function () {
                return 'hello world';
            });
        });

        $this->seeResponse('hello world', Request::create('users', 'GET'));
    }

    public function testCanOverrideGroupControllerWithInvokableControllerSyntax()
    {
        $this->router->controller(RouteRegistrarControllerStub::class)->group(function ($router) {
            $router->get('users', InvokableRouteRegistrarControllerStub::class);
        });

        $this->assertSame(
            InvokableRouteRegistrarControllerStub::class.'@__invoke',
            $this->getRoute()->getAction()['uses']
        );
    }

    public function testWillUseTheLatestGroupController()
    {
        $this->router->controller(RouteRegistrarControllerStub::class)->group(function ($router) {
            $router->group(['controller' => FooController::class], function ($router) {
                $router->get('users', 'index');
            });
        });

        $this->assertSame(
            FooController::class.'@index',
            $this->getRoute()->getAction()['uses']
        );
    }

    public function testCanOverrideGroupControllerWithArraySyntax()
    {
        $this->router->controller(RouteRegistrarControllerStub::class)->group(function ($router) {
            $router->get('users', [FooController::class, 'index']);
        });

        $this->assertSame(
            FooController::class.'@index',
            $this->getRoute()->getAction()['uses']
        );
    }

    public function testRouteGroupingWithoutPrefix()
    {
        $this->router->group([], function ($router) {
            $router->prefix('bar')->get('baz', ['as' => 'baz', function () {
                return 'hello';
            }]);
        });
        $this->seeResponse('hello', Request::create('bar/baz', 'GET'));
    }

    public function testRouteGroupChaining()
    {
        $this->router
            ->group([], function ($router) {
                $router->get('foo', function () {
                    return 'hello';
                });
            })
            ->group([], function ($router) {
                $router->get('bar', function () {
                    return 'goodbye';
                });
            });

        $routeCollection = $this->router->getRoutes();

        $this->assertInstanceOf(\Illuminate\Routing\Route::class, $routeCollection->match(Request::create('foo', 'GET')));
        $this->assertInstanceOf(\Illuminate\Routing\Route::class, $routeCollection->match(Request::create('bar', 'GET')));
    }

    public function testRegisteringNonApprovedAttributesThrows()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method Illuminate\Routing\RouteRegistrar::unsupportedMethod does not exist.');

        $this->router->domain('foo')->unsupportedMethod('bar')->group(function ($router) {
            //
        });
    }

    public function testCanRegisterResource()
    {
        $this->router->middleware('resource-middleware')
                     ->resource('users', RouteRegistrarControllerStub::class);

        $this->seeResponse('deleted', Request::create('users/1', 'DELETE'));
        $this->seeMiddleware('resource-middleware');
    }

    public function testCanRegisterResourcesWithExceptOption()
    {
        $this->router->resources([
            'resource-one' => RouteRegistrarControllerStubOne::class,
            'resource-two' => RouteRegistrarControllerStubTwo::class,
            'resource-three' => RouteRegistrarControllerStubThree::class,
        ], ['except' => ['create', 'show']]);

        $this->assertCount(15, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));
        }
    }

    public function testCanRegisterResourcesWithOnlyOption()
    {
        $this->router->resources([
            'resource-one' => RouteRegistrarControllerStubOne::class,
            'resource-two' => RouteRegistrarControllerStubTwo::class,
            'resource-three' => RouteRegistrarControllerStubThree::class,
        ], ['only' => ['create', 'show']]);

        $this->assertCount(6, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));
        }
    }

    public function testCanRegisterResourcesWithoutOption()
    {
        $this->router->resources([
            'resource-one' => RouteRegistrarControllerStubOne::class,
            'resource-two' => RouteRegistrarControllerStubTwo::class,
            'resource-three' => RouteRegistrarControllerStubThree::class,
        ]);

        $this->assertCount(21, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));
        }
    }

    public function testCanRegisterResourceWithMissingOption()
    {
        $this->router->middleware('resource-middleware')
            ->resource('users', RouteRegistrarControllerStub::class)
            ->missing(function () {
                return 'missing';
            });

        $this->assertIsCallable($this->router->getRoutes()->getByName('users.show')->getMissing());
        $this->assertIsCallable($this->router->getRoutes()->getByName('users.edit')->getMissing());
        $this->assertIsCallable($this->router->getRoutes()->getByName('users.update')->getMissing());
        $this->assertIsCallable($this->router->getRoutes()->getByName('users.destroy')->getMissing());

        $this->assertNull($this->router->getRoutes()->getByName('users.index')->getMissing());
        $this->assertNull($this->router->getRoutes()->getByName('users.create')->getMissing());
        $this->assertNull($this->router->getRoutes()->getByName('users.store')->getMissing());
    }

    public function testCanAccessRegisteredResourceRoutesAsRouteCollection()
    {
        $resource = $this->router->middleware('resource-middleware')
                     ->resource('users', RouteRegistrarControllerStub::class)
                     ->register();

        $this->assertCount(7, $resource->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.index'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.create'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.store'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.edit'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanLimitMethodsOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->only('index', 'show', 'destroy');

        $this->assertCount(3, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.index'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanExcludeMethodsOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->except(['index', 'create', 'store', 'show', 'edit']);

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanLimitAndExcludeMethodsOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->only('index', 'show', 'destroy')
                     ->except('destroy');

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.index'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.show'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanSetShallowOptionOnRegisteredResource()
    {
        $this->router->resource('users.tasks', RouteRegistrarControllerStub::class)->shallow();

        $this->assertCount(7, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.tasks.index'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('tasks.show'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.tasks.show'));
    }

    public function testCanSetScopedOptionOnRegisteredResource()
    {
        $this->router->resource('users.tasks', RouteRegistrarControllerStub::class)->scoped();
        $this->assertSame(
            ['user' => null],
            $this->router->getRoutes()->getByName('users.tasks.index')->bindingFields()
        );
        $this->assertSame(
            ['user' => null, 'task' => null],
            $this->router->getRoutes()->getByName('users.tasks.show')->bindingFields()
        );

        $this->router->resource('users.tasks', RouteRegistrarControllerStub::class)->scoped([
            'task' => 'slug',
        ]);
        $this->assertSame(
            ['user' => null],
            $this->router->getRoutes()->getByName('users.tasks.index')->bindingFields()
        );
        $this->assertSame(
            ['user' => null, 'task' => 'slug'],
            $this->router->getRoutes()->getByName('users.tasks.show')->bindingFields()
        );
    }

    public function testCanExcludeMethodsOnRegisteredApiResource()
    {
        $this->router->apiResource('users', RouteRegistrarControllerStub::class)
                     ->except(['index', 'show', 'store']);

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testCanRegisterApiResourcesWithExceptOption()
    {
        $this->router->apiResources([
            'resource-one' => RouteRegistrarControllerStubOne::class,
            'resource-two' => RouteRegistrarControllerStubTwo::class,
            'resource-three' => RouteRegistrarControllerStubThree::class,
        ], ['except' => ['create', 'show']]);

        $this->assertCount(12, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
        }
    }

    public function testCanRegisterApiResourcesWithOnlyOption()
    {
        $this->router->apiResources([
            'resource-one' => RouteRegistrarControllerStubOne::class,
            'resource-two' => RouteRegistrarControllerStubTwo::class,
            'resource-three' => RouteRegistrarControllerStubThree::class,
        ], ['only' => ['index', 'show']]);

        $this->assertCount(6, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
        }
    }

    public function testCanRegisterApiResourcesWithoutOption()
    {
        $this->router->apiResources([
            'resource-one' => RouteRegistrarControllerStubOne::class,
            'resource-two' => RouteRegistrarControllerStubTwo::class,
            'resource-three' => RouteRegistrarControllerStubThree::class,
        ]);

        $this->assertCount(15, $this->router->getRoutes());

        foreach (['one', 'two', 'three'] as $resource) {
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.index'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.show'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.store'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.update'));
            $this->assertTrue($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.destroy'));

            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.create'));
            $this->assertFalse($this->router->getRoutes()->hasNamedRoute('resource-'.$resource.'.edit'));
        }
    }

    public function testUserCanRegisterApiResource()
    {
        $this->router->apiResource('users', RouteRegistrarControllerStub::class);

        $this->assertCount(5, $this->router->getRoutes());

        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.create'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.edit'));
    }

    public function testUserCanRegisterApiResourceWithExceptOption()
    {
        $this->router->apiResource('users', RouteRegistrarControllerStub::class, [
            'except' => ['destroy'],
        ]);

        $this->assertCount(4, $this->router->getRoutes());

        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.create'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.edit'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('users.destroy'));
    }

    public function testUserCanRegisterApiResourceWithOnlyOption()
    {
        $this->router->apiResource('users', RouteRegistrarControllerStub::class, [
            'only' => ['index', 'show'],
        ]);

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.index'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('users.show'));
    }

    public function testCanNameRoutesOnRegisteredResource()
    {
        $this->router->resource('comments', RouteRegistrarControllerStub::class)
                     ->only('create', 'store')->names('reply');

        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->only('create', 'store')->names([
                         'create' => 'user.build',
                         'store' => 'user.save',
                     ]);

        $this->router->resource('posts', RouteRegistrarControllerStub::class)
                    ->only('create', 'destroy')
                    ->name('create', 'posts.make')
                    ->name('destroy', 'posts.remove');

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('reply.create'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('reply.store'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.build'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.save'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('posts.make'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('posts.remove'));
    }

    public function testCanOverrideParametersOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->parameters(['users' => 'admin_user']);

        $this->router->resource('posts', RouteRegistrarControllerStub::class)
                     ->parameter('posts', 'topic');

        $this->assertStringContainsString('admin_user', $this->router->getRoutes()->getByName('users.show')->uri);
        $this->assertStringContainsString('topic', $this->router->getRoutes()->getByName('posts.show')->uri);
    }

    public function testCanSetMiddlewareOnRegisteredResource()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->middleware(RouteRegistrarMiddlewareStub::class);

        $this->seeMiddleware(RouteRegistrarMiddlewareStub::class);
    }

    public function testResourceWithoutMiddlewareRegistration()
    {
        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->only('index')
                     ->middleware(['one', 'two'])
                     ->withoutMiddleware('one');

        $this->seeResponse('controller', Request::create('users', 'GET'));

        $this->assertEquals(['one'], $this->getRoute()->excludedMiddleware());
    }

    public function testResourceWithMiddlewareAsStringable()
    {
        $one = new class implements Stringable
        {
            public function __toString()
            {
                return 'one';
            }
        };

        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->only('index')
                     ->middleware([$one, 'two'])
                     ->withoutMiddleware('one');

        $this->seeResponse('controller', Request::create('users', 'GET'));

        $this->assertEquals(['one', 'two'], $this->getRoute()->middleware());
        $this->assertEquals(['one'], $this->getRoute()->excludedMiddleware());
    }

    public function testResourceWheres()
    {
        $wheres = [
            'user' => '\d+',
            'test' => '[a-z]+',
        ];

        $this->router->resource('users', RouteRegistrarControllerStub::class)
                     ->where($wheres);

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testWhereNumberRegistration()
    {
        $wheres = ['foo' => '[0-9]+', 'bar' => '[0-9]+'];

        $this->router->get('/{foo}/{bar}')->whereNumber(['foo', 'bar']);
        $this->router->get('/api/{bar}/{foo}')->whereNumber(['bar', 'foo']);

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testWhereAlphaRegistration()
    {
        $wheres = ['foo' => '[a-zA-Z]+', 'bar' => '[a-zA-Z]+'];

        $this->router->get('/{foo}/{bar}')->whereAlpha(['foo', 'bar']);
        $this->router->get('/api/{bar}/{foo}')->whereAlpha(['bar', 'foo']);

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testWhereAlphaNumericRegistration()
    {
        $wheres = ['1a2b3c' => '[a-zA-Z0-9]+'];

        $this->router->get('/{foo}')->whereAlphaNumeric(['1a2b3c']);

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testWhereInRegistration()
    {
        $wheres = ['foo' => 'one|two', 'bar' => 'one|two'];

        $this->router->get('/{foo}/{bar}')->whereIn(['foo', 'bar'], ['one', 'two']);
        $this->router->get('/api/{bar}/{foo}')->whereIn(['bar', 'foo'], ['one', 'two']);

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testWhereInEnumRegistration()
    {
        $this->router->get('/posts/{category}')->whereIn('category', CategoryBackedEnum::cases());

        $invalidRequest = Request::create('/posts/invalid-value', 'GET');
        $this->assertFalse($this->getRoute()->matches($invalidRequest));

        foreach (CategoryBackedEnum::cases() as $case) {
            $request = Request::create('/posts/'.$case->value, 'GET');
            $this->assertTrue($this->getRoute()->matches($request));
        }
    }

    public function testGroupWhereNumberRegistrationOnRouteRegistrar()
    {
        $wheres = ['foo' => '[0-9]+', 'bar' => '[0-9]+'];

        $this->router->prefix('/{foo}/{bar}')->whereNumber(['foo', 'bar'])->group(function ($router) {
            $router->get('/');
        });

        $this->router->prefix('/api/{bar}/{foo}')->whereNumber(['bar', 'foo'])->group(function ($router) {
            $router->get('/');
        });

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testGroupWhereAlphaRegistrationOnRouteRegistrar()
    {
        $wheres = ['foo' => '[a-zA-Z]+', 'bar' => '[a-zA-Z]+'];

        $this->router->prefix('/{foo}/{bar}')->whereAlpha(['foo', 'bar'])->group(function ($router) {
            $router->get('/');
        });

        $this->router->prefix('/api/{bar}/{foo}')->whereAlpha(['bar', 'foo'])->group(function ($router) {
            $router->get('/');
        });

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testGroupWhereAlphaNumericRegistrationOnRouteRegistrar()
    {
        $wheres = ['1a2b3c' => '[a-zA-Z0-9]+'];

        $this->router->prefix('/{foo}')->whereAlphaNumeric(['1a2b3c'])->group(function ($router) {
            $router->get('/');
        });

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testGroupWhereInRegistrationOnRouteRegistrar()
    {
        $wheres = ['foo' => 'one|two', 'bar' => 'one|two'];

        $this->router->prefix('/{foo}/{bar}')->whereIn(['foo', 'bar'], ['one', 'two'])->group(function ($router) {
            $router->get('/');
        });

        $this->router->prefix('/api/{bar}/{foo}')->whereIn(['bar', 'foo'], ['one', 'two'])->group(function ($router) {
            $router->get('/');
        });

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testGroupWhereNumberRegistrationOnRouter()
    {
        $wheres = ['foo' => '[0-9]+', 'bar' => '[0-9]+'];

        $this->router->whereNumber(['foo', 'bar'])->prefix('/{foo}/{bar}')->group(function ($router) {
            $router->get('/');
        });

        $this->router->whereNumber(['bar', 'foo'])->prefix('/api/{bar}/{foo}')->group(function ($router) {
            $router->get('/');
        });

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testGroupWhereAlphaRegistrationOnRouter()
    {
        $wheres = ['foo' => '[a-zA-Z]+', 'bar' => '[a-zA-Z]+'];

        $this->router->whereAlpha(['foo', 'bar'])->prefix('/{foo}/{bar}')->group(function ($router) {
            $router->get('/');
        });

        $this->router->whereAlpha(['bar', 'foo'])->prefix('/api/{bar}/{foo}')->group(function ($router) {
            $router->get('/');
        });

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testGroupWhereAlphaNumericRegistrationOnRouter()
    {
        $wheres = ['1a2b3c' => '[a-zA-Z0-9]+'];

        $this->router->whereAlphaNumeric(['1a2b3c'])->prefix('/{foo}')->group(function ($router) {
            $router->get('/');
        });

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testGroupWhereInRegistrationOnRouter()
    {
        $wheres = ['foo' => 'one|two', 'bar' => 'one|two'];

        $this->router->whereIn(['foo', 'bar'], ['one', 'two'])->prefix('/{foo}/{bar}')->group(function ($router) {
            $router->get('/');
        });

        $this->router->whereIn(['bar', 'foo'], ['one', 'two'])->prefix('/api/{bar}/{foo}')->group(function ($router) {
            $router->get('/');
        });

        /** @var \Illuminate\Routing\Route $route */
        foreach ($this->router->getRoutes() as $route) {
            $this->assertEquals($wheres, $route->wheres);
        }
    }

    public function testCanSetRouteName()
    {
        $this->router->as('users.index')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertSame('users.index', $this->getRoute()->getName());
    }

    public function testCanSetRouteNameUsingNameAlias()
    {
        $this->router->name('users.index')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertSame('users.index', $this->getRoute()->getName());
    }

    public function testCanSetRouteNameUsingStringBackedEnum()
    {
        $this->router->name(RouteNameEnum::UserIndex)->get('users', fn () => 'all-users');

        $this->assertSame('users.index', $this->getRoute()->getName());
    }

    public function testCannotSetRouteNameUsingIntegerBackedEnum()
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Attribute [name] expects a string backed enum.'));

        $this->router->name(IntegerEnum::One)->get('users', fn () => 'all-users');
    }

    public function testCanSetRouteDomainUsingStringBackedEnum()
    {
        $this->router->domain(RouteDomainEnum::DashboardDomain)->get('users', fn () => 'all-users');

        $this->assertSame('dashboard.myapp.com', $this->getRoute()->getDomain());
    }

    public function testCannotSetRouteDomainUsingIntegerBackedEnum()
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Attribute [domain] expects a string backed enum.'));

        $this->router->domain(IntegerEnum::One)->get('users', fn () => 'all-users');
    }

    public function testPushMiddlewareToGroup()
    {
        $this->router->middlewareGroup('web', []);
        $this->router->pushMiddlewareToGroup('web', 'test-middleware');

        $this->assertEquals(['test-middleware'], $this->router->getMiddlewareGroups()['web']);
    }

    public function testPushMiddlewareToGroupUnregisteredGroup()
    {
        $this->router->pushMiddlewareToGroup('web', 'test-middleware');

        $this->assertEquals(['test-middleware'], $this->router->getMiddlewareGroups()['web']);
    }

    public function testPushMiddlewareToGroupDuplicatedMiddleware()
    {
        $this->router->pushMiddlewareToGroup('web', 'test-middleware');
        $this->router->pushMiddlewareToGroup('web', 'test-middleware');

        $this->assertEquals(['test-middleware'], $this->router->getMiddlewareGroups()['web']);
    }

    public function testCanRemoveMiddlewareFromGroup()
    {
        $this->router->pushMiddlewareToGroup('web', 'test-middleware');

        $this->router->removeMiddlewareFromGroup('web', 'test-middleware');

        $this->assertEquals([], $this->router->getMiddlewareGroups()['web']);
    }

    public function testCanRemoveMiddlewareFromGroupNotUnregisteredMiddleware()
    {
        $this->router->middlewareGroup('web', []);

        $this->router->removeMiddlewareFromGroup('web', 'different-test-middleware');

        $this->assertEquals([], $this->router->getMiddlewareGroups()['web']);
    }

    public function testCanRemoveMiddlewareFromGroupUnregisteredGroup()
    {
        $this->router->removeMiddlewareFromGroup('web', ['test-middleware']);

        $this->assertEquals([], $this->router->getMiddlewareGroups());
    }

    public function testCanRegisterSingleton()
    {
        $this->router->singleton('user', RouteRegistrarControllerStub::class);

        $this->assertCount(3, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.edit'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.update'));
    }

    public function testCanRegisterApiSingleton()
    {
        $this->router->apiSingleton('user', RouteRegistrarControllerStub::class);

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.update'));
    }

    public function testCanRegisterCreatableSingleton()
    {
        $this->router->singleton('user', RouteRegistrarControllerStub::class)->creatable();

        $this->assertCount(6, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.create'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.store'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.edit'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.destroy'));
    }

    public function testCanRegisterCreatableApiSingleton()
    {
        $this->router->apiSingleton('user', RouteRegistrarControllerStub::class)->creatable();

        $this->assertCount(4, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.store'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.destroy'));
    }

    public function testSingletonCreatableNotDestroyable()
    {
        $this->router->singleton('user', RouteRegistrarControllerStub::class)
            ->creatable()
            ->except('destroy');

        $this->assertCount(5, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.create'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.store'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.edit'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.update'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('user.destroy'));
    }

    public function testApiSingletonCreatableNotDestroyable()
    {
        $this->router->apiSingleton('user', RouteRegistrarControllerStub::class)
            ->creatable()
            ->except('destroy');

        $this->assertCount(3, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.store'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.update'));
        $this->assertFalse($this->router->getRoutes()->hasNamedRoute('user.destroy'));
    }

    public function testSingletonCanBeDestroyable()
    {
        $this->router->singleton('user', RouteRegistrarControllerStub::class)
            ->destroyable();

        $this->assertCount(4, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.edit'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.destroy'));
    }

    public function testApiSingletonCanBeDestroyable()
    {
        $this->router->apiSingleton('user', RouteRegistrarControllerStub::class)
            ->destroyable();

        $this->assertCount(3, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.show'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.update'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.destroy'));
    }

    public function testSingletonCanBeOnlyCreatable()
    {
        $this->router->singleton('user', RouteRegistrarControllerStub::class)
            ->creatable()
            ->only('create', 'store');

        $this->assertCount(2, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.create'));
        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.store'));
    }

    public function testApiSingletonCanBeOnlyCreatable()
    {
        $this->router->apiSingleton('user', RouteRegistrarControllerStub::class)
            ->creatable()
            ->only('store');

        $this->assertCount(1, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.store'));
    }

    public function testSingletonDoesntAllowIncludingUnsupportedMethods()
    {
        $this->router->singleton('post', RouteRegistrarControllerStub::class)
            ->only('index', 'store', 'create', 'destroy');

        $this->assertCount(0, $this->router->getRoutes());

        $this->router->apiSingleton('user', RouteRegistrarControllerStub::class)
            ->only('index', 'store', 'create', 'destroy');

        $this->assertCount(0, $this->router->getRoutes());
    }

    public function testApiSingletonCanIncludeAnySingletonMethods()
    {
        // This matches the behavior of the apiResource method.
        $this->router->apiSingleton('user', RouteRegistrarControllerStub::class)
            ->only('edit');

        $this->assertCount(1, $this->router->getRoutes());

        $this->assertTrue($this->router->getRoutes()->hasNamedRoute('user.edit'));
    }

    /**
     * Get the last route registered with the router.
     *
     * @return \Illuminate\Routing\Route
     */
    protected function getRoute()
    {
        return last($this->router->getRoutes()->get());
    }

    /**
     * Assert that the last route has the given middleware.
     *
     * @param  string  $middleware
     * @return void
     */
    protected function seeMiddleware($middleware)
    {
        $this->assertEquals($middleware, $this->getRoute()->middleware()[0]);
    }

    /**
     * Assert that the last route has the given content.
     *
     * @param  string  $content
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function seeResponse($content, Request $request)
    {
        $route = $this->getRoute();

        $this->assertTrue($route->matches($request));

        $this->assertEquals($content, $route->bind($request)->run());
    }
}

class RouteRegistrarControllerStub
{
    public function index()
    {
        return 'controller';
    }

    public function destroy()
    {
        return 'deleted';
    }
}

class InvokableRouteRegistrarControllerStub
{
    public function __invoke()
    {
        return 'controller';
    }
}

class RouteRegistrarMiddlewareStub
{
    //
}
