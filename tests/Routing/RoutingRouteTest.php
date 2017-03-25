<?php

namespace Illuminate\Tests\Routing;

use stdClass;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use UnexpectedValueException;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Controller;
use Illuminate\Routing\RouteGroup;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Auth\Middleware\Authenticate;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Routing\Middleware\SubstituteBindings;

class RoutingRouteTest extends TestCase
{
    public function testBasicDispatchingOfRoutes()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(new Response('hello'));
        });
        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $route = $router->get('foo/bar', ['domain' => 'api.{name}.bar', function ($name) {
            return $name;
        }]);
        $route = $router->get('foo/bar', ['domain' => 'api.{name}.baz', function ($name) {
            return $name;
        }]);
        $this->assertEquals('taylor', $router->dispatch(Request::create('http://api.taylor.bar/foo/bar', 'GET'))->getContent());
        $this->assertEquals('dayle', $router->dispatch(Request::create('http://api.dayle.baz/foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $route = $router->get('foo/{age}', ['domain' => 'api.{name}.bar', function ($name, $age) {
            return $name.$age;
        }]);
        $this->assertEquals('taylor25', $router->dispatch(Request::create('http://api.taylor.bar/foo/25', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $router->post('foo/bar', function () {
            return 'post hello';
        });
        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertEquals('post hello', $router->dispatch(Request::create('foo/bar', 'POST'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/{bar}', function ($name) {
            return $name;
        });
        $this->assertEquals('taylor', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/{bar}/{baz?}', function ($name, $age = 25) {
            return $name.$age;
        });
        $this->assertEquals('taylor25', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/{name}/boom/{age?}/{location?}', function ($name, $age = 25, $location = 'AR') {
            return $name.$age.$location;
        });
        $this->assertEquals('taylor30AR', $router->dispatch(Request::create('foo/taylor/boom/30', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('{bar}/{baz?}', function ($name, $age = 25) {
            return $name.$age;
        });
        $this->assertEquals('taylor25', $router->dispatch(Request::create('taylor', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('{baz?}', function ($age = 25) {
            return $age;
        });
        $this->assertEquals('25', $router->dispatch(Request::create('/', 'GET'))->getContent());
        $this->assertEquals('30', $router->dispatch(Request::create('30', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('{foo?}/{baz?}', ['as' => 'foo', function ($name = 'taylor', $age = 25) {
            return $name.$age;
        }]);
        $this->assertEquals('taylor25', $router->dispatch(Request::create('/', 'GET'))->getContent());
        $this->assertEquals('fred25', $router->dispatch(Request::create('fred', 'GET'))->getContent());
        $this->assertEquals('fred30', $router->dispatch(Request::create('fred/30', 'GET'))->getContent());
        $this->assertTrue($router->currentRouteNamed('foo'));
        $this->assertTrue($router->is('foo'));
        $this->assertFalse($router->is('bar'));

        $router = $this->getRouter();
        $route = $router->get('foo/{file}', function ($file) {
            return $file;
        });
        $this->assertEquals('oxygen%20', $router->dispatch(Request::create('http://test.com/foo/oxygen%2520', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->patch('foo/bar', ['as' => 'foo', function () {
            return 'bar';
        }]);
        $this->assertEquals('bar', $router->dispatch(Request::create('foo/bar', 'PATCH'))->getContent());
        $this->assertEquals('foo', $router->currentRouteName());

        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $this->assertEmpty($router->dispatch(Request::create('foo/bar', 'HEAD'))->getContent());

        $router = $this->getRouter();
        $router->any('foo/bar', function () {
            return 'hello';
        });
        $this->assertEmpty($router->dispatch(Request::create('foo/bar', 'HEAD'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'first';
        });
        $router->get('foo/bar', function () {
            return 'second';
        });
        $this->assertEquals('second', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar/åαф', function () {
            return 'hello';
        });
        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar/%C3%A5%CE%B1%D1%84', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar', ['boom' => 'auth', function () {
            return 'closure';
        }]);
        $this->assertEquals('closure', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    public function testClosureMiddleware()
    {
        $router = $this->getRouter();
        $middleware = function ($request, $next) {
            return 'caught';
        };
        $router->get('foo/bar', ['middleware' => $middleware, function () {
            return 'hello';
        }]);
        $this->assertEquals('caught', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    public function testDefinedClosureMiddleware()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', ['middleware' => 'foo', function () {
            return 'hello';
        }]);
        $router->aliasMiddleware('foo', function ($request, $next) {
            return 'caught';
        });
        $this->assertEquals('caught', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    public function testControllerClosureMiddleware()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', [
            'uses' => 'Illuminate\Tests\Routing\RouteTestClosureMiddlewareController@index',
            'middleware' => 'foo',
        ]);
        $router->aliasMiddleware('foo', function ($request, $next) {
            $request['foo-middleware'] = 'foo-middleware';

            return $next($request);
        });

        $this->assertEquals(
            'index-foo-middleware-controller-closure',
            $router->dispatch(Request::create('foo/bar', 'GET'))->getContent()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Route for [foo/bar] has no action.
     */
    public function testFluentRouting()
    {
        $router = $this->getRouter();
        $router->get('foo/bar')->uses(function () {
            return 'hello';
        });
        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $router->post('foo/bar')->uses(function () {
            return 'hello';
        });
        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'POST'))->getContent());
        $router->get('foo/bar')->uses(function () {
            return 'middleware';
        })->middleware('Illuminate\Tests\Routing\RouteTestControllerMiddleware');
        $this->assertEquals('middleware', $router->dispatch(Request::create('foo/bar'))->getContent());
        $this->assertContains('Illuminate\Tests\Routing\RouteTestControllerMiddleware', $router->getCurrentRoute()->middleware());
        $router->get('foo/bar');
        $router->dispatch(Request::create('foo/bar', 'GET'));
    }

    public function testFluentRoutingWithControllerAction()
    {
        $router = $this->getRouter();
        $router->get('foo/bar')->uses('Illuminate\Tests\Routing\RouteTestControllerStub@index');
        $this->assertEquals('Hello World', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->group(['namespace' => 'App'], function ($router) {
            $router->get('foo/bar')->uses('Illuminate\Tests\Routing\RouteTestControllerStub@index');
        });
        $action = $router->getRoutes()->getRoutes()[0]->getAction();
        $this->assertEquals('App\\Illuminate\Tests\Routing\RouteTestControllerStub@index', $action['controller']);
    }

    public function testMiddlewareGroups()
    {
        unset($_SERVER['__middleware.group']);
        $router = $this->getRouter();
        $router->get('foo/bar', ['middleware' => 'web', function () {
            return 'hello';
        }]);

        $router->aliasMiddleware('two', 'Illuminate\Tests\Routing\RoutingTestMiddlewareGroupTwo');
        $router->middlewareGroup('web', ['Illuminate\Tests\Routing\RoutingTestMiddlewareGroupOne', 'two:taylor']);

        $this->assertEquals('caught taylor', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertTrue($_SERVER['__middleware.group']);

        unset($_SERVER['__middleware.group']);
    }

    public function testMiddlewareGroupsCanReferenceOtherGroups()
    {
        unset($_SERVER['__middleware.group']);
        $router = $this->getRouter();
        $router->get('foo/bar', ['middleware' => 'web', function () {
            return 'hello';
        }]);

        $router->aliasMiddleware('two', 'Illuminate\Tests\Routing\RoutingTestMiddlewareGroupTwo');
        $router->middlewareGroup('first', ['two:abigail']);
        $router->middlewareGroup('web', ['Illuminate\Tests\Routing\RoutingTestMiddlewareGroupOne', 'first']);

        $this->assertEquals('caught abigail', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertTrue($_SERVER['__middleware.group']);

        unset($_SERVER['__middleware.group']);
    }

    public function testFluentRouteNamingWithinAGroup()
    {
        $router = $this->getRouter();
        $router->group(['as' => 'foo.'], function () use ($router) {
            $router->get('bar', function () {
                return 'bar';
            })->name('bar');
        });
        $this->assertEquals('bar', $router->dispatch(Request::create('bar', 'GET'))->getContent());
        $this->assertEquals('foo.bar', $router->currentRouteName());
    }

    public function testMacro()
    {
        $router = $this->getRouter();
        $router->macro('webhook', function () use ($router) {
            $router->match(['GET', 'POST'], 'webhook', function () {
                return 'OK';
            });
        });
        $router->webhook();
        $this->assertEquals('OK', $router->dispatch(Request::create('webhook', 'GET'))->getContent());
        $this->assertEquals('OK', $router->dispatch(Request::create('webhook', 'POST'))->getContent());
    }

    public function testClassesCanBeInjectedIntoRoutes()
    {
        unset($_SERVER['__test.route_inject']);
        $router = $this->getRouter();
        $router->get('foo/{var}', function (stdClass $foo, $var) {
            $_SERVER['__test.route_inject'] = func_get_args();

            return 'hello';
        });

        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertInstanceOf('stdClass', $_SERVER['__test.route_inject'][0]);
        $this->assertEquals('bar', $_SERVER['__test.route_inject'][1]);

        unset($_SERVER['__test.route_inject']);
    }

    public function testClassesAndVariablesCanBeInjectedIntoRoutes()
    {
        unset($_SERVER['__test.route_inject']);
        $router = $this->getRouter();
        $router->get('foo/{var}/{bar?}/{baz?}', function (stdClass $foo, $var, $bar = 'test', stdClass $baz = null) {
            $_SERVER['__test.route_inject'] = func_get_args();

            return 'hello';
        });
        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertInstanceOf('stdClass', $_SERVER['__test.route_inject'][0]);
        $this->assertEquals('bar', $_SERVER['__test.route_inject'][1]);
        $this->assertEquals('test', $_SERVER['__test.route_inject'][2]);
        $this->assertNull($_SERVER['__test.route_inject'][3]);
        $this->assertArrayHasKey(3, $_SERVER['__test.route_inject']);
        unset($_SERVER['__test.route_inject']);
    }

    public function testOptionsResponsesAreGeneratedByDefault()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $router->post('foo/bar', function () {
            return 'hello';
        });
        $response = $router->dispatch(Request::create('foo/bar', 'OPTIONS'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('GET,HEAD,POST', $response->headers->get('Allow'));
    }

    public function testHeadDispatcher()
    {
        $router = $this->getRouter();
        $router->match(['GET', 'POST'], 'foo', function () {
            return 'bar';
        });

        $response = $router->dispatch(Request::create('foo', 'OPTIONS'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('GET,HEAD,POST', $response->headers->get('Allow'));

        $response = $router->dispatch(Request::create('foo', 'HEAD'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());

        $router = $this->getRouter();
        $router->match(['GET'], 'foo', function () {
            return 'bar';
        });

        $response = $router->dispatch(Request::create('foo', 'OPTIONS'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('GET,HEAD', $response->headers->get('Allow'));

        $router = $this->getRouter();
        $router->match(['POST'], 'foo', function () {
            return 'bar';
        });

        $response = $router->dispatch(Request::create('foo', 'OPTIONS'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('POST', $response->headers->get('Allow'));
    }

    public function testNonGreedyMatches()
    {
        $route = new Route('GET', 'images/{id}.{ext}', function () {
        });

        $request1 = Request::create('images/1.png', 'GET');
        $this->assertTrue($route->matches($request1));
        $route->bind($request1);
        $this->assertTrue($route->hasParameter('id'));
        $this->assertFalse($route->hasParameter('foo'));
        $this->assertEquals('1', $route->parameter('id'));
        $this->assertEquals('png', $route->parameter('ext'));

        $request2 = Request::create('images/12.png', 'GET');
        $this->assertTrue($route->matches($request2));
        $route->bind($request2);
        $this->assertEquals('12', $route->parameter('id'));
        $this->assertEquals('png', $route->parameter('ext'));

        // Test parameter() default value
        $route = new Route('GET', 'foo/{foo?}', function () {
        });

        $request3 = Request::create('foo', 'GET');
        $this->assertTrue($route->matches($request3));
        $route->bind($request3);
        $this->assertEquals('bar', $route->parameter('foo', 'bar'));
    }

    public function testRouteParametersDefaultValue()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar?}', ['uses' => 'Illuminate\Tests\Routing\RouteTestControllerWithParameterStub@returnParameter'])->defaults('bar', 'foo');
        $this->assertEquals('foo', $router->dispatch(Request::create('foo', 'GET'))->getContent());

        $router->get('foo/{bar?}', ['uses' => 'Illuminate\Tests\Routing\RouteTestControllerWithParameterStub@returnParameter'])->defaults('bar', 'foo');
        $this->assertEquals('bar', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router->get('foo/{bar?}', function ($bar = '') {
            return $bar;
        })->defaults('bar', 'foo');
        $this->assertEquals('foo', $router->dispatch(Request::create('foo', 'GET'))->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRoutesDontMatchNonMatchingPathsWithLeadingOptionals()
    {
        $router = $this->getRouter();
        $router->get('{baz?}', function ($age = 25) {
            return $age;
        });
        $this->assertEquals('25', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRoutesDontMatchNonMatchingDomain()
    {
        $router = $this->getRouter();
        $route = $router->get('foo/bar', ['domain' => 'api.foo.bar', function () {
            return 'hello';
        }]);
        $this->assertEquals('hello', $router->dispatch(Request::create('http://api.baz.boom/foo/bar', 'GET'))->getContent());
    }

    public function testMatchesMethodAgainstRequests()
    {
        /*
         * Basic
         */
        $request = Request::create('foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', function () {
        });
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/bar', 'GET');
        $route = new Route('GET', 'foo', function () {
        });
        $this->assertFalse($route->matches($request));

        /*
         * Method checks
         */
        $request = Request::create('foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', function () {
        });
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/bar', 'POST');
        $route = new Route('GET', 'foo', function () {
        });
        $this->assertFalse($route->matches($request));

        /*
         * Domain checks
         */
        $request = Request::create('http://something.foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['domain' => '{foo}.foo.com', function () {
        }]);
        $this->assertTrue($route->matches($request));

        $request = Request::create('http://something.bar.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['domain' => '{foo}.foo.com', function () {
        }]);
        $this->assertFalse($route->matches($request));

        /*
         * HTTPS checks
         */
        $request = Request::create('https://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['https', function () {
        }]);
        $this->assertTrue($route->matches($request));

        $request = Request::create('https://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['https', 'baz' => true, function () {
        }]);
        $this->assertTrue($route->matches($request));

        $request = Request::create('http://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['https', function () {
        }]);
        $this->assertFalse($route->matches($request));

        /*
         * HTTP checks
         */
        $request = Request::create('https://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['http', function () {
        }]);
        $this->assertFalse($route->matches($request));

        $request = Request::create('http://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['http', function () {
        }]);
        $this->assertTrue($route->matches($request));

        $request = Request::create('http://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['baz' => true, function () {
        }]);
        $this->assertTrue($route->matches($request));
    }

    public function testWherePatternsProperlyFilter()
    {
        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar}', function () {
        });
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123abc', 'GET');
        $route = new Route('GET', 'foo/{bar}', function () {
        });
        $route->where('bar', '[0-9]+');
        $this->assertFalse($route->matches($request));

        $request = Request::create('foo/123abc', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['where' => ['bar' => '[0-9]+'], function () {
        }]);
        $route->where('bar', '[0-9]+');
        $this->assertFalse($route->matches($request));

        /*
         * Optional
         */
        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar?}', function () {
        });
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar?}', ['where' => ['bar' => '[0-9]+'], function () {
        }]);
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar?}/{baz?}', function () {
        });
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123/foo', 'GET');
        $route = new Route('GET', 'foo/{bar?}/{baz?}', function () {
        });
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123abc', 'GET');
        $route = new Route('GET', 'foo/{bar?}', function () {
        });
        $route->where('bar', '[0-9]+');
        $this->assertFalse($route->matches($request));
    }

    public function testDotDoesNotMatchEverything()
    {
        $route = new Route('GET', 'images/{id}.{ext}', function () {
        });

        $request1 = Request::create('images/1.png', 'GET');
        $this->assertTrue($route->matches($request1));
        $route->bind($request1);
        $this->assertEquals('1', $route->parameter('id'));
        $this->assertEquals('png', $route->parameter('ext'));

        $request2 = Request::create('images/12.png', 'GET');
        $this->assertTrue($route->matches($request2));
        $route->bind($request2);
        $this->assertEquals('12', $route->parameter('id'));
        $this->assertEquals('png', $route->parameter('ext'));
    }

    public function testRouteBinding()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->bind('bar', function ($value) {
            return strtoupper($value);
        });
        $this->assertEquals('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testRouteClassBinding()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->bind('bar', 'Illuminate\Tests\Routing\RouteBindingStub');
        $this->assertEquals('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testRouteClassMethodBinding()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->bind('bar', 'Illuminate\Tests\Routing\RouteBindingStub@find');
        $this->assertEquals('dragon', $router->dispatch(Request::create('foo/Dragon', 'GET'))->getContent());
    }

    public function testMiddlewarePrioritySorting()
    {
        $middleware = [
            Placeholder1::class,
            SubstituteBindings::class,
            Placeholder2::class,
            Authenticate::class,
            Placeholder3::class,
        ];

        $router = $this->getRouter();

        $router->middlewarePriority = [Authenticate::class, SubstituteBindings::class, Authorize::class];

        $route = $router->get('foo', ['middleware' => $middleware, 'uses' => function ($name) {
            return $name;
        }]);

        $this->assertEquals([
            Placeholder1::class,
            Authenticate::class,
            SubstituteBindings::class,
            Placeholder2::class,
            Placeholder3::class,
        ], $router->gatherRouteMiddleware($route));
    }

    public function testModelBinding()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', 'Illuminate\Tests\Routing\RouteModelBindingStub');
        $this->assertEquals('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testModelBindingWithNullReturn()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', 'Illuminate\Tests\Routing\RouteModelBindingNullStub');
        $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent();
    }

    public function testModelBindingWithCustomNullReturn()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', 'Illuminate\Tests\Routing\RouteModelBindingNullStub', function () {
            return 'missing';
        });
        $this->assertEquals('missing', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testModelBindingWithBindingClosure()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', 'Illuminate\Tests\Routing\RouteModelBindingNullStub', function ($value) {
            return (new RouteModelBindingClosureStub())->findAlternate($value);
        });
        $this->assertEquals('tayloralt', $router->dispatch(Request::create('foo/TAYLOR', 'GET'))->getContent());
    }

    public function testModelBindingWithCompoundParameterName()
    {
        $router = $this->getRouter();
        $router->resource('foo-bar', 'Illuminate\Tests\Routing\RouteTestResourceControllerWithModelParameter', ['middleware' => SubstituteBindings::class]);
        $this->assertEquals('12345', $router->dispatch(Request::create('foo-bar/12345', 'GET'))->getContent());
    }

    /**
     * @group shit
     */
    public function testModelBindingWithCompoundParameterNameAndRouteBinding()
    {
        $router = $this->getRouter();
        $router->model('foo_bar', 'Illuminate\Tests\Routing\RoutingTestUserModel');
        $router->resource('foo-bar', 'Illuminate\Tests\Routing\RouteTestResourceControllerWithModelParameter', ['middleware' => SubstituteBindings::class]);
        $this->assertEquals('12345', $router->dispatch(Request::create('foo-bar/12345', 'GET'))->getContent());
    }

    public function testModelBindingThroughIOC()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $container->bind('Illuminate\Tests\Routing\RouteModelInterface', 'Illuminate\Tests\Routing\RouteModelBindingStub');
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', 'Illuminate\Tests\Routing\RouteModelInterface');
        $this->assertEquals('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testGroupMerging()
    {
        $old = ['prefix' => 'foo/bar/'];
        $this->assertEquals(['prefix' => 'foo/bar/baz', 'namespace' => null, 'where' => []], RouteGroup::merge(['prefix' => 'baz'], $old));

        $old = ['domain' => 'foo'];
        $this->assertEquals(['domain' => 'baz', 'prefix' => null, 'namespace' => null, 'where' => []], RouteGroup::merge(['domain' => 'baz'], $old));

        $old = ['as' => 'foo.'];
        $this->assertEquals(['as' => 'foo.bar', 'prefix' => null, 'namespace' => null, 'where' => []], RouteGroup::merge(['as' => 'bar'], $old));

        $old = ['where' => ['var1' => 'foo', 'var2' => 'bar']];
        $this->assertEquals(['prefix' => null, 'namespace' => null, 'where' => [
            'var1' => 'foo', 'var2' => 'baz', 'var3' => 'qux',
        ]], RouteGroup::merge(['where' => ['var2' => 'baz', 'var3' => 'qux']], $old));

        $old = [];
        $this->assertEquals(['prefix' => null, 'namespace' => null, 'where' => [
            'var1' => 'foo', 'var2' => 'bar',
        ]], RouteGroup::merge(['where' => ['var1' => 'foo', 'var2' => 'bar']], $old));
    }

    public function testRouteGrouping()
    {
        /*
         * getPrefix() method
         */
        $router = $this->getRouter();
        $router->group(['prefix' => 'foo'], function () use ($router) {
            $router->get('bar', function () {
                return 'hello';
            });
        });
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();
        $this->assertEquals('foo', $routes[0]->getPrefix());
    }

    public function testRouteGroupingFromFile()
    {
        $router = $this->getRouter();
        $router->group(['prefix' => 'api'], __DIR__.'/fixtures/routes.php');

        $route = last($router->getRoutes()->get());
        $request = Request::create('api/users', 'GET');

        $this->assertTrue($route->matches($request));
        $this->assertEquals('all-users', $route->bind($request)->run($request));
    }

    public function testRouteGroupingWithAs()
    {
        $router = $this->getRouter();
        $router->group(['prefix' => 'foo', 'as' => 'Foo::'], function () use ($router) {
            $router->get('bar', ['as' => 'bar', function () {
                return 'hello';
            }]);
        });
        $routes = $router->getRoutes();
        $route = $routes->getByName('Foo::bar');
        $this->assertEquals('foo/bar', $route->uri());
    }

    public function testNestedRouteGroupingWithAs()
    {
        /*
         * nested with all layers present
         */
        $router = $this->getRouter();
        $router->group(['prefix' => 'foo', 'as' => 'Foo::'], function () use ($router) {
            $router->group(['prefix' => 'bar', 'as' => 'Bar::'], function () use ($router) {
                $router->get('baz', ['as' => 'baz', function () {
                    return 'hello';
                }]);
            });
        });
        $routes = $router->getRoutes();
        $route = $routes->getByName('Foo::Bar::baz');
        $this->assertEquals('foo/bar/baz', $route->uri());

        /*
         * nested with layer skipped
         */
        $router = $this->getRouter();
        $router->group(['prefix' => 'foo', 'as' => 'Foo::'], function () use ($router) {
            $router->group(['prefix' => 'bar'], function () use ($router) {
                $router->get('baz', ['as' => 'baz', function () {
                    return 'hello';
                }]);
            });
        });
        $routes = $router->getRoutes();
        $route = $routes->getByName('Foo::baz');
        $this->assertEquals('foo/bar/baz', $route->uri());
    }

    public function testRouteMiddlewareMergeWithMiddlewareAttributesAsStrings()
    {
        $router = $this->getRouter();
        $router->group(['prefix' => 'foo', 'middleware' => 'boo:foo'], function () use ($router) {
            $router->get('bar', function () {
                return 'hello';
            })->middleware('baz:gaz');
        });
        $routes = $router->getRoutes()->getRoutes();
        $route = $routes[0];
        $this->assertEquals(
            ['boo:foo', 'baz:gaz'],
            $route->middleware()
        );
    }

    public function testRoutePrefixing()
    {
        /*
         * Prefix route
         */
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();
        $routes[0]->prefix('prefix');
        $this->assertEquals('prefix/foo/bar', $routes[0]->uri());

        /*
         * Use empty prefix
         */
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();
        $routes[0]->prefix('/');
        $this->assertEquals('foo/bar', $routes[0]->uri());

        /*
         * Prefix homepage
         */
        $router = $this->getRouter();
        $router->get('/', function () {
            return 'hello';
        });
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();
        $routes[0]->prefix('prefix');
        $this->assertEquals('prefix', $routes[0]->uri());
    }

    public function testMergingControllerUses()
    {
        $router = $this->getRouter();
        $router->group(['namespace' => 'Namespace'], function () use ($router) {
            $router->get('foo/bar', 'Controller@action');
        });
        $routes = $router->getRoutes()->getRoutes();
        $action = $routes[0]->getAction();

        $this->assertEquals('Namespace\\Controller@action', $action['controller']);

        $router = $this->getRouter();
        $router->group(['namespace' => 'Namespace'], function () use ($router) {
            $router->group(['namespace' => 'Nested'], function () use ($router) {
                $router->get('foo/bar', 'Controller@action');
            });
        });
        $routes = $router->getRoutes()->getRoutes();
        $action = $routes[0]->getAction();

        $this->assertEquals('Namespace\\Nested\\Controller@action', $action['controller']);

        $router = $this->getRouter();
        $router->group(['prefix' => 'baz'], function () use ($router) {
            $router->group(['namespace' => 'Namespace'], function () use ($router) {
                $router->get('foo/bar', 'Controller@action');
            });
        });
        $routes = $router->getRoutes()->getRoutes();
        $action = $routes[0]->getAction();

        $this->assertEquals('Namespace\\Controller@action', $action['controller']);
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testInvalidActionException()
    {
        $router = $this->getRouter();
        $router->get('/', ['uses' => 'Illuminate\Tests\Routing\RouteTestControllerStub']);

        $router->dispatch(Request::create('/'));
    }

    public function testResourceRouting()
    {
        $router = $this->getRouter();
        $router->resource('foo', 'FooController');
        $routes = $router->getRoutes();
        $this->assertCount(7, $routes);

        $router = $this->getRouter();
        $router->resource('foo', 'FooController', ['only' => ['update']]);
        $routes = $router->getRoutes();

        $this->assertCount(1, $routes);

        $router = $this->getRouter();
        $router->resource('foo', 'FooController', ['only' => ['show', 'destroy']]);
        $routes = $router->getRoutes();

        $this->assertCount(2, $routes);

        $router = $this->getRouter();
        $router->resource('foo', 'FooController', ['except' => ['show', 'destroy']]);
        $routes = $router->getRoutes();

        $this->assertCount(5, $routes);

        $router = $this->getRouter();
        $router->resource('foo-bars', 'FooController', ['only' => ['show']]);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertEquals('foo-bars/{foo_bar}', $routes[0]->uri());

        $router = $this->getRouter();
        $router->resource('foo-bar.foo-baz', 'FooController', ['only' => ['show']]);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertEquals('foo-bar/{foo_bar}/foo-baz/{foo_baz}', $routes[0]->uri());

        $router = $this->getRouter();
        $router->resource('foo-bars', 'FooController', ['only' => ['show'], 'as' => 'prefix']);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertEquals('foo-bars/{foo_bar}', $routes[0]->uri());
        $this->assertEquals('prefix.foo-bars.show', $routes[0]->getName());

        ResourceRegistrar::verbs([
            'create' => 'ajouter',
            'edit' => 'modifier',
        ]);
        $router = $this->getRouter();
        $router->resource('foo', 'FooController');
        $routes = $router->getRoutes();

        $this->assertEquals('foo/ajouter', $routes->getByName('foo.create')->uri());
        $this->assertEquals('foo/{foo}/modifier', $routes->getByName('foo.edit')->uri());
    }

    public function testResourceRoutingParameters()
    {
        ResourceRegistrar::singularParameters();

        $router = $this->getRouter();
        $router->resource('foos', 'FooController');
        $router->resource('foos.bars', 'FooController');
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertEquals('foos/{foo}', $routes[3]->uri());
        $this->assertEquals('foos/{foo}/bars/{bar}', $routes[10]->uri());

        ResourceRegistrar::setParameters(['foos' => 'oof', 'bazs' => 'b']);

        $router = $this->getRouter();
        $router->resource('bars.foos.bazs', 'FooController');
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertEquals('bars/{bar}/foos/{oof}/bazs/{b}', $routes[3]->uri());

        ResourceRegistrar::setParameters();
        ResourceRegistrar::singularParameters(false);

        $router = $this->getRouter();
        $router->resource('foos', 'FooController', ['parameters' => 'singular']);
        $router->resource('foos.bars', 'FooController', ['parameters' => 'singular']);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertEquals('foos/{foo}', $routes[3]->uri());
        $this->assertEquals('foos/{foo}/bars/{bar}', $routes[10]->uri());

        $router = $this->getRouter();
        $router->resource('foos.bars', 'FooController', ['parameters' => ['foos' => 'foo', 'bars' => 'bar']]);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertEquals('foos/{foo}/bars/{bar}', $routes[3]->uri());
    }

    public function testResourceRouteNaming()
    {
        $router = $this->getRouter();
        $router->resource('foo', 'FooController');

        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.index'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.show'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.create'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.store'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.edit'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.update'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.destroy'));

        $router = $this->getRouter();
        $router->resource('foo.bar', 'FooController');

        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.index'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.show'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.create'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.store'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.edit'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.update'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.destroy'));

        $router = $this->getRouter();
        $router->resource('prefix/foo.bar', 'FooController');

        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.index'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.show'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.create'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.store'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.edit'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.update'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.destroy'));

        $router = $this->getRouter();
        $router->resource('foo', 'FooController', ['names' => [
            'index' => 'foo',
            'show' => 'bar',
        ]]);

        $this->assertTrue($router->getRoutes()->hasNamedRoute('foo'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('bar'));

        $router = $this->getRouter();
        $router->resource('foo', 'FooController', ['names' => 'bar']);

        $this->assertTrue($router->getRoutes()->hasNamedRoute('bar.index'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('bar.show'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('bar.create'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('bar.store'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('bar.edit'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('bar.update'));
        $this->assertTrue($router->getRoutes()->hasNamedRoute('bar.destroy'));
    }

    public function testRouterFiresRoutedEvent()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $router->get('foo/bar', function () {
            return '';
        });

        $request = Request::create('http://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/bar', ['http', function () {
        }]);

        $_SERVER['__router.request'] = null;
        $_SERVER['__router.route'] = null;

        $router->matched(function ($event) {
            $_SERVER['__router.request'] = $event->request;
            $_SERVER['__router.route'] = $event->route;
        });

        $router->dispatchToRoute($request);

        $this->assertInstanceOf('Illuminate\Http\Request', $_SERVER['__router.request']);
        $this->assertEquals($_SERVER['__router.request'], $request);
        unset($_SERVER['__router.request']);

        $this->assertInstanceOf('Illuminate\Routing\Route', $_SERVER['__router.route']);
        $this->assertEquals($_SERVER['__router.route']->uri(), $route->uri());
        unset($_SERVER['__router.route']);
    }

    public function testRouterPatternSetting()
    {
        $router = $this->getRouter();
        $router->pattern('test', 'pattern');
        $this->assertEquals(['test' => 'pattern'], $router->getPatterns());

        $router = $this->getRouter();
        $router->patterns(['test' => 'pattern', 'test2' => 'pattern2']);
        $this->assertEquals(['test' => 'pattern', 'test2' => 'pattern2'], $router->getPatterns());
    }

    public function testControllerRouting()
    {
        unset(
            $_SERVER['route.test.controller.middleware'], $_SERVER['route.test.controller.except.middleware'],
            $_SERVER['route.test.controller.middleware.class'],
            $_SERVER['route.test.controller.middleware.parameters.one'], $_SERVER['route.test.controller.middleware.parameters.two']
        );

        $router = $this->getRouter();

        $router->get('foo/bar', 'Illuminate\Tests\Routing\RouteTestControllerStub@index');

        $this->assertEquals('Hello World', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertTrue($_SERVER['route.test.controller.middleware']);
        $this->assertEquals('Illuminate\Http\Response', $_SERVER['route.test.controller.middleware.class']);
        $this->assertEquals(0, $_SERVER['route.test.controller.middleware.parameters.one']);
        $this->assertEquals(['foo', 'bar'], $_SERVER['route.test.controller.middleware.parameters.two']);
        $this->assertFalse(isset($_SERVER['route.test.controller.except.middleware']));
    }

    public function testControllerMiddlewareGroups()
    {
        unset(
            $_SERVER['route.test.controller.middleware'],
            $_SERVER['route.test.controller.middleware.class']
        );

        $router = $this->getRouter();

        $router->middlewareGroup('web', [
            'Illuminate\Tests\Routing\RouteTestControllerMiddleware',
            'Illuminate\Tests\Routing\RouteTestControllerMiddlewareTwo',
        ]);

        $router->get('foo/bar', 'Illuminate\Tests\Routing\RouteTestControllerMiddlewareGroupStub@index');

        $this->assertEquals('caught', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertTrue($_SERVER['route.test.controller.middleware']);
        $this->assertEquals('Illuminate\Http\Response', $_SERVER['route.test.controller.middleware.class']);
    }

    public function testImplicitBindings()
    {
        $phpunit = $this;
        $router = $this->getRouter();
        $router->get('foo/{bar}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar) use ($phpunit) {
                $phpunit->assertInstanceOf(RoutingTestUserModel::class, $bar);

                return $bar->value;
            },
        ]);
        $this->assertEquals('taylor', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testImplicitBindingsWithOptionalParameterWithExistingKeyInUri()
    {
        $phpunit = $this;
        $router = $this->getRouter();
        $router->get('foo/{bar?}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar = null) use ($phpunit) {
                $phpunit->assertInstanceOf(RoutingTestUserModel::class, $bar);

                return $bar->value;
            },
        ]);
        $this->assertEquals('taylor', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testImplicitBindingsWithOptionalParameterWithNoKeyInUri()
    {
        $phpunit = $this;
        $router = $this->getRouter();
        $router->get('foo/{bar?}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar = null) use ($phpunit) {
                $phpunit->assertNull($bar);
            },
        ]);
        $router->dispatch(Request::create('foo', 'GET'))->getContent();
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testImplicitBindingsWithOptionalParameterWithNonExistingKeyInUri()
    {
        $phpunit = $this;
        $router = $this->getRouter();
        $router->get('foo/{bar?}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestNonExistingUserModel $bar = null) use ($phpunit) {
                $phpunit->fail('ModelNotFoundException was expected.');
            },
        ]);
        $router->dispatch(Request::create('foo/nonexisting', 'GET'))->getContent();
    }

    public function testImplicitBindingThroughIOC()
    {
        $phpunit = $this;
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });

        $container->bind('Illuminate\Tests\Routing\RoutingTestUserModel', 'Illuminate\Tests\Routing\RoutingTestExtendedUserModel');
        $router->get('foo/{bar}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar) use ($phpunit) {
                $phpunit->assertInstanceOf(RoutingTestExtendedUserModel::class, $bar);
            },
        ]);
        $router->dispatch(Request::create('foo/baz', 'GET'))->getContent();
    }

    public function testDispatchingCallableActionClasses()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', 'Illuminate\Tests\Routing\ActionStub');

        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router->get('foo/bar2', [
            'uses' => 'Illuminate\Tests\Routing\ActionStub',
        ]);

        $this->assertEquals('hello', $router->dispatch(Request::create('foo/bar2', 'GET'))->getContent());
    }

    public function testResponseIsReturned()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });

        $response = $router->dispatch(Request::create('foo/bar', 'GET'));
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertNotInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    public function testJsonResponseIsReturned()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return ['foo', 'bar'];
        });

        $response = $router->dispatch(Request::create('foo/bar', 'GET'));
        $this->assertNotInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    protected function getRouter()
    {
        $container = new Container;

        $router = new Router(new Dispatcher, $container);

        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });

        return $router;
    }
}

class RouteTestControllerStub extends Controller
{
    public function __construct()
    {
        $this->middleware('Illuminate\Tests\Routing\RouteTestControllerMiddleware');
        $this->middleware('Illuminate\Tests\Routing\RouteTestControllerParameterizedMiddlewareOne:0');
        $this->middleware('Illuminate\Tests\Routing\RouteTestControllerParameterizedMiddlewareTwo:foo,bar');
        $this->middleware('Illuminate\Tests\Routing\RouteTestControllerExceptMiddleware', ['except' => 'index']);
    }

    public function index()
    {
        return 'Hello World';
    }
}

class RouteTestControllerMiddlewareGroupStub extends Controller
{
    public function __construct()
    {
        $this->middleware('web');
    }

    public function index()
    {
        return 'Hello World';
    }
}

class RouteTestControllerWithParameterStub extends Controller
{
    public function returnParameter($bar = '')
    {
        return $bar;
    }
}

class RouteTestResourceControllerWithModelParameter extends Controller
{
    public function show(RoutingTestUserModel $fooBar)
    {
        return $fooBar->value;
    }
}

class RouteTestClosureMiddlewareController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $response = $next($request);

            return $response->setContent(
                $response->content().'-'.$request['foo-middleware'].'-controller-closure'
            );
        });
    }

    public function index()
    {
        return 'index';
    }
}

class RouteTestControllerMiddleware
{
    public function handle($request, $next)
    {
        $_SERVER['route.test.controller.middleware'] = true;
        $response = $next($request);
        $_SERVER['route.test.controller.middleware.class'] = get_class($response);

        return $response;
    }
}

class RouteTestControllerMiddlewareTwo
{
    public function handle($request, $next)
    {
        return new \Illuminate\Http\Response('caught');
    }
}

class RouteTestControllerParameterizedMiddlewareOne
{
    public function handle($request, $next, $parameter)
    {
        $_SERVER['route.test.controller.middleware.parameters.one'] = $parameter;

        return $next($request);
    }
}

class RouteTestControllerParameterizedMiddlewareTwo
{
    public function handle($request, $next, $parameter1, $parameter2)
    {
        $_SERVER['route.test.controller.middleware.parameters.two'] = [$parameter1, $parameter2];

        return $next($request);
    }
}

class RouteTestControllerExceptMiddleware
{
    public function handle($request, $next)
    {
        $_SERVER['route.test.controller.except.middleware'] = true;

        return $next($request);
    }
}

class RouteBindingStub
{
    public function bind($value, $route)
    {
        return strtoupper($value);
    }

    public function find($value, $route)
    {
        return strtolower($value);
    }
}

class RouteModelBindingStub
{
    public function getRouteKeyName()
    {
        return 'id';
    }

    public function where($key, $value)
    {
        $this->value = $value;

        return $this;
    }

    public function first()
    {
        return strtoupper($this->value);
    }
}

class RouteModelBindingNullStub
{
    public function getRouteKeyName()
    {
        return 'id';
    }

    public function where($key, $value)
    {
        return $this;
    }

    public function first()
    {
    }
}

class RouteModelBindingClosureStub
{
    public function findAlternate($value)
    {
        return strtolower($value).'alt';
    }
}

class RoutingTestMiddlewareGroupOne
{
    public function handle($request, $next)
    {
        $_SERVER['__middleware.group'] = true;

        return $next($request);
    }
}

class RoutingTestMiddlewareGroupTwo
{
    public function handle($request, $next, $parameter = null)
    {
        return new \Illuminate\Http\Response('caught '.$parameter);
    }
}

class RoutingTestUserModel extends Model
{
    public function getRouteKeyName()
    {
        return 'id';
    }

    public function where($key, $value)
    {
        $this->value = $value;

        return $this;
    }

    public function first()
    {
        return $this;
    }

    public function firstOrFail()
    {
        return $this;
    }
}

class RoutingTestExtendedUserModel extends RoutingTestUserModel
{
}

class RoutingTestNonExistingUserModel extends RoutingTestUserModel
{
    public function first()
    {
    }

    public function firstOrFail()
    {
        throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
    }
}

class ActionStub
{
    public function __invoke()
    {
        return 'hello';
    }
}
