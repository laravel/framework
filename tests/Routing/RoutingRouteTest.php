<?php

namespace Illuminate\Tests\Routing;

use Closure;
use DateTime;
use Exception;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Routing\Controller;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Events\PreparingResponse;
use Illuminate\Routing\Events\ResponsePrepared;
use Illuminate\Routing\Events\Routing;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\ResourceRegistrar;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\RouteGroup;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Str;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

class RoutingRouteTest extends TestCase
{
    public function testBasicDispatchingOfRoutes()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            throw new HttpResponseException(new Response('hello'));
        });
        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar', ['domain' => 'api.{name}.bar', function ($name) {
            return $name;
        }]);
        $router->get('foo/bar', ['domain' => 'api.{name}.baz', function ($name) {
            return $name;
        }]);
        $this->assertSame('taylor', $router->dispatch(Request::create('http://api.taylor.bar/foo/bar', 'GET'))->getContent());
        $this->assertSame('dayle', $router->dispatch(Request::create('http://api.dayle.baz/foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/{age}', ['domain' => 'api.{name}.bar', function ($name, $age) {
            return $name.$age;
        }]);
        $this->assertSame('taylor25', $router->dispatch(Request::create('http://api.taylor.bar/foo/25', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $router->post('foo/bar', function () {
            return 'post hello';
        });
        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertSame('post hello', $router->dispatch(Request::create('foo/bar', 'POST'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/{bar}', function ($name) {
            return $name;
        });
        $this->assertSame('taylor', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/{bar}/{baz?}', function ($name, $age = 25) {
            return $name.$age;
        });
        $this->assertSame('taylor25', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/{name}/boom/{age?}/{location?}', function ($name, $age = 25, $location = 'AR') {
            return $name.$age.$location;
        });
        $this->assertSame('taylor30AR', $router->dispatch(Request::create('foo/taylor/boom/30', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('{bar}/{baz?}', function ($name, $age = 25) {
            return $name.$age;
        });
        $this->assertSame('taylor25', $router->dispatch(Request::create('taylor', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('{baz?}', function ($age = 25) {
            return $age;
        });
        $this->assertSame('25', $router->dispatch(Request::create('/', 'GET'))->getContent());
        $this->assertSame('30', $router->dispatch(Request::create('30', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('{foo?}/{baz?}', ['as' => 'foo', function ($name = 'taylor', $age = 25) {
            return $name.$age;
        }]);
        $this->assertSame('taylor25', $router->dispatch(Request::create('/', 'GET'))->getContent());
        $this->assertSame('fred25', $router->dispatch(Request::create('fred', 'GET'))->getContent());
        $this->assertSame('fred30', $router->dispatch(Request::create('fred/30', 'GET'))->getContent());
        $this->assertTrue($router->currentRouteNamed('foo'));
        $this->assertTrue($router->currentRouteNamed('fo*'));
        $this->assertTrue($router->is('foo'));
        $this->assertTrue($router->is('foo', 'bar'));
        $this->assertFalse($router->is('bar'));

        $router = $this->getRouter();
        $router->get('foo/{file}', function ($file) {
            return $file;
        });
        $this->assertSame('oxygen%20', $router->dispatch(Request::create('http://test.com/foo/oxygen%2520', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->patch('foo/bar', ['as' => 'foo', function () {
            return 'bar';
        }]);
        $this->assertSame('bar', $router->dispatch(Request::create('foo/bar', 'PATCH'))->getContent());
        $this->assertSame('foo', $router->currentRouteName());

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
        $this->assertSame('second', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar/åαф', function () {
            return 'hello';
        });
        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar/%C3%A5%CE%B1%D1%84', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->get('foo/bar', ['boom' => 'auth', function () {
            return 'closure';
        }]);
        $this->assertSame('closure', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    public function testNotModifiedResponseIsProperlyReturned()
    {
        $router = $this->getRouter();
        $router->get('test', function () {
            return (new SymfonyResponse('test', 304, ['foo' => 'bar']))->setLastModified(new DateTime);
        });

        $response = $router->dispatch(Request::create('test', 'GET'));
        $this->assertSame(304, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
        $this->assertSame('bar', $response->headers->get('foo'));
        $this->assertNull($response->getLastModified());
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
        $this->assertSame('caught', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    public function testMiddlewareCanBeSkipped()
    {
        $router = $this->getRouter();
        $router->aliasMiddleware('web', RoutingTestMiddlewareGroupTwo::class);

        $router->get('foo/bar', ['middleware' => 'web', function () {
            return 'hello';
        }])->withoutMiddleware(RoutingTestMiddlewareGroupTwo::class);

        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    public function testMiddlewareCanBeSkippedFromResources()
    {
        $router = $this->getRouter();
        $router->aliasMiddleware('web', RoutingTestMiddlewareGroupTwo::class);

        $router->resource('foo', RouteTestControllerMiddlewareGroupStub::class)
            ->middleware('web')
            ->withoutMiddleware(RoutingTestMiddlewareGroupTwo::class);

        $this->assertSame('Hello World', $router->dispatch(Request::create('foo', 'GET'))->getContent());
    }

    public function testMiddlewareWorksIfControllerThrowsHttpResponseException()
    {
        // Before calling controller
        $router = $this->getRouter();
        $middleware = function ($request, $next) {
            return 'caught';
        };
        $router->get('foo/bar', ['middleware' => $middleware, function () {
            throw new HttpResponseException(new Response('hello'));
        }]);
        $response = $router->dispatch(Request::create('foo/bar', 'GET'))->getContent();
        $this->assertSame('caught', $response);

        // After calling controller
        $router = $this->getRouter();

        $response = new Response('hello');

        $middleware = function ($request, $next) use ($response) {
            $this->assertSame($response, $next($request));

            return new Response($response->getContent().' caught');
        };
        $router->get('foo/bar', ['middleware' => $middleware, function () use ($response) {
            throw new HttpResponseException($response);
        }]);

        $response = $router->dispatch(Request::create('foo/bar', 'GET'))->getContent();
        $this->assertSame('hello caught', $response);
    }

    public function testReturnsResponseWhenMiddlewareReturnsResponsable()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', [
            'uses' => RouteTestClosureMiddlewareController::class.'@index',
            'middleware' => ['foo', 'bar', 'baz'],
        ]);
        $router->aliasMiddleware('foo', function ($request, $next) {
            return $next($request);
        });
        $router->aliasMiddleware('bar', function ($request, $next) {
            return new ResponsableResponse;
        });
        $router->aliasMiddleware('baz', function ($request, $next) {
            return $next($request);
        });
        $this->assertSame(
            'bar',
            $router->dispatch(Request::create('foo/bar', 'GET'))->getContent()
        );
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
        $this->assertSame('caught', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    public function testControllerClosureMiddleware()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', [
            'uses' => RouteTestClosureMiddlewareController::class.'@index',
            'middleware' => 'foo',
        ]);
        $router->aliasMiddleware('foo', function ($request, $next) {
            $request['foo-middleware'] = 'foo-middleware';

            return $next($request);
        });

        $this->assertSame(
            'index-foo-middleware-controller-closure',
            $router->dispatch(Request::create('foo/bar', 'GET'))->getContent()
        );
    }

    public function testFluentRouting()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Route for [foo/bar] has no action.');

        $router = $this->getRouter();
        $router->get('foo/bar')->uses(function () {
            return 'hello';
        });
        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $router->post('foo/bar')->uses(function () {
            return 'hello';
        });
        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar', 'POST'))->getContent());
        $router->get('foo/bar')->uses(function () {
            return 'middleware';
        })->middleware(RouteTestControllerMiddleware::class);
        $this->assertSame('middleware', $router->dispatch(Request::create('foo/bar'))->getContent());
        $this->assertContains(RouteTestControllerMiddleware::class, $router->getCurrentRoute()->middleware());
        $router->get('foo/bar');
        $router->dispatch(Request::create('foo/bar', 'GET'));
    }

    public function testFluentRoutingWithControllerAction()
    {
        $router = $this->getRouter();
        $router->get('foo/bar')->uses(RouteTestControllerStub::class.'@index');
        $this->assertSame('Hello World', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router = $this->getRouter();
        $router->group(['namespace' => 'App'], function ($router) {
            $router->get('foo/bar')->uses(RouteTestControllerStub::class.'@index');
        });
        $action = $router->getRoutes()->getRoutes()[0]->getAction();
        $this->assertSame('App\\'.RouteTestControllerStub::class.'@index', $action['controller']);
    }

    public function testMiddlewareGroups()
    {
        unset($_SERVER['__middleware.group']);
        $router = $this->getRouter();
        $router->get('foo/bar', ['middleware' => 'web', function () {
            return 'hello';
        }]);

        $router->aliasMiddleware('two', RoutingTestMiddlewareGroupTwo::class);
        $router->middlewareGroup('web', [RoutingTestMiddlewareGroupOne::class, 'two:taylor']);

        $this->assertSame('caught taylor', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
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

        $router->aliasMiddleware('two', RoutingTestMiddlewareGroupTwo::class);
        $router->middlewareGroup('first', ['two:abigail']);
        $router->middlewareGroup('web', [RoutingTestMiddlewareGroupOne::class, 'first']);

        $this->assertSame('caught abigail', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
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
        $this->assertSame('bar', $router->dispatch(Request::create('bar', 'GET'))->getContent());
        $this->assertSame('foo.bar', $router->currentRouteName());
    }

    public function testRouteGetAction()
    {
        $router = $this->getRouter();

        $route = $router->get('foo', function () {
            return 'foo';
        })->name('foo');

        $this->assertIsArray($route->getAction());
        $this->assertArrayHasKey('as', $route->getAction());
        $this->assertSame('foo', $route->getAction('as'));
        $this->assertNull($route->getAction('unknown_property'));
    }

    public function testRouteGetControllerClass()
    {
        $router = $this->getRouter();

        $controllerRoute = $router->get('foo/bar')->uses(RouteTestControllerStub::class.'@index');
        $closureRoute = $router->get('foo', function () {
            return 'foo';
        });

        $this->assertSame(RouteTestControllerStub::class, $controllerRoute->getControllerClass());
        $this->assertNull($closureRoute->getControllerClass());
    }

    public function testResolvingBindingParameters()
    {
        $router = $this->getRouter();

        $route = $router->get('foo/{bar:slug}', function () {
            return 'foo';
        })->name('foo');

        $this->assertSame('slug', $route->bindingFieldFor('bar'));

        $route = $router->get('foo/{bar:slug}/{baz}', function () {
            return 'foo';
        })->name('foo');

        $this->assertNull($route->bindingFieldFor('baz'));
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
        $this->assertSame('OK', $router->dispatch(Request::create('webhook', 'GET'))->getContent());
        $this->assertSame('OK', $router->dispatch(Request::create('webhook', 'POST'))->getContent());
    }

    public function testRouteMacro()
    {
        $router = $this->getRouter();

        Route::macro('breadcrumb', function ($breadcrumb) {
            $this->action['breadcrumb'] = $breadcrumb;

            return $this;
        });

        $router->get('foo', function () {
            return 'bar';
        })->breadcrumb('fooBreadcrumb')->name('foo');

        $router->getRoutes()->refreshNameLookups();

        $this->assertSame('fooBreadcrumb', $router->getRoutes()->getByName('foo')->getAction()['breadcrumb']);
    }

    public function testClassesCanBeInjectedIntoRoutes()
    {
        unset($_SERVER['__test.route_inject']);
        $router = $this->getRouter();
        $router->get('foo/{var}', function (stdClass $foo, $var) {
            $_SERVER['__test.route_inject'] = func_get_args();

            return 'hello';
        });

        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertInstanceOf(stdClass::class, $_SERVER['__test.route_inject'][0]);
        $this->assertSame('bar', $_SERVER['__test.route_inject'][1]);

        unset($_SERVER['__test.route_inject']);
    }

    public function testNullValuesCanBeInjectedIntoRoutes()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });

        $container->bind(RoutingTestUserModel::class, function () {
        });
        $container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        $router->get('foo/{team}/{post}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (?RoutingTestUserModel $userFromContainer, RoutingTestTeamModel $team, $postId) {
                $this->assertNull($userFromContainer);
                $this->assertInstanceOf(RoutingTestTeamModel::class, $team);
                $this->assertSame('bar', $team->value);
                $this->assertSame('baz', $postId);
            },
        ]);
        $router->dispatch(Request::create('foo/bar/baz', 'GET'))->getContent();
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
        $this->assertSame('GET,HEAD,POST', $response->headers->get('Allow'));
    }

    public function testHeadDispatcher()
    {
        $router = $this->getRouter();
        $router->match(['GET', 'POST'], 'foo', function () {
            return 'bar';
        });

        $response = $router->dispatch(Request::create('foo', 'OPTIONS'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('GET,HEAD,POST', $response->headers->get('Allow'));

        $response = $router->dispatch(Request::create('foo', 'HEAD'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());

        $router = $this->getRouter();
        $router->match(['GET'], 'foo', function () {
            return 'bar';
        });

        $response = $router->dispatch(Request::create('foo', 'OPTIONS'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('GET,HEAD', $response->headers->get('Allow'));

        $router = $this->getRouter();
        $router->match(['POST'], 'foo', function () {
            return 'bar';
        });

        $response = $router->dispatch(Request::create('foo', 'OPTIONS'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('POST', $response->headers->get('Allow'));
    }

    public function testNonGreedyMatches()
    {
        $route = new Route('GET', 'images/{id}.{ext}', function () {
            //
        });

        $request1 = Request::create('images/1.png', 'GET');
        $this->assertTrue($route->matches($request1));
        $route->bind($request1);
        $this->assertTrue($route->hasParameter('id'));
        $this->assertFalse($route->hasParameter('foo'));
        $this->assertSame('1', (string) $route->parameter('id'));
        $this->assertSame('png', $route->parameter('ext'));

        $request2 = Request::create('images/12.png', 'GET');
        $this->assertTrue($route->matches($request2));
        $route->bind($request2);
        $this->assertSame('12', $route->parameter('id'));
        $this->assertSame('png', $route->parameter('ext'));

        // Test parameter() default value
        $route = new Route('GET', 'foo/{foo?}', function () {
            //
        });

        $request3 = Request::create('foo', 'GET');
        $this->assertTrue($route->matches($request3));
        $route->bind($request3);
        $this->assertSame('bar', $route->parameter('foo', 'bar'));
    }

    public function testHasParameters()
    {
        $route = new Route('GET', 'images/{id}.{ext}', function () {
            //
        });
        $request1 = Request::create('images/1.png', 'GET');
        $this->assertFalse($route->hasParameters());
        $this->assertTrue($route->matches($request1));
        $route->bind($request1);
        $this->assertTrue($route->hasParameters());
    }

    public function testForgetParameter()
    {
        $route = new Route('GET', 'images/{id}.{ext}', function () {
            //
        });
        $request1 = Request::create('images/1.png', 'GET');
        $route->bind($request1);
        $this->assertTrue($route->hasParameter('id'));
        $this->assertTrue($route->hasParameter('ext'));
        $route->forgetParameter('id');
        $this->assertFalse($route->hasParameter('id'));
        $this->assertTrue($route->hasParameter('ext'));
    }

    public function testParameterNames()
    {
        $route = new Route('GET', 'images/{id}.{ext}', function () {
            //
        });
        $this->assertSame(['id', 'ext'], $route->parameterNames());

        $route = new Route('GET', 'foo/{bar?}', function () {
            //
        });
        $this->assertSame(['bar'], $route->parameterNames());

        $route = new Route('GET', '/', function () {
            //
        });
        $this->assertSame([], $route->parameterNames());
    }

    public function testParametersWithoutNulls()
    {
        $route = new Route('GET', 'users/{id?}/{name?}/', function () {
            //
        });
        $request1 = Request::create('users/12/amir', 'GET');
        $route->bind($request1);
        $this->assertSame(['id' => '12', 'name' => 'amir'], $route->parametersWithoutNulls());

        $route = new Route('GET', 'users/{id?}/{name?}/', function () {
            //
        });
        $request1 = Request::create('users/12', 'GET');
        $route->bind($request1);
        $this->assertSame(['id' => '12'], $route->parametersWithoutNulls());

        $route = new Route('GET', 'users/{id?}/{name?}/', function () {
            //
        });
        $request1 = Request::create('users/', 'GET');
        $route->bind($request1);
        $this->assertSame([], $route->parametersWithoutNulls());
    }

    public function testRouteParametersDefaultValue()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar?}', ['uses' => RouteTestControllerWithParameterStub::class.'@returnParameter'])->defaults('bar', 'foo');
        $this->assertSame('foo', $router->dispatch(Request::create('foo', 'GET'))->getContent());

        $router->get('foo/{bar?}', ['uses' => RouteTestControllerWithParameterStub::class.'@returnParameter'])->defaults('bar', 'foo');
        $this->assertSame('bar', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router->get('foo/{bar?}', function ($bar = '') {
            return $bar;
        })->defaults('bar', 'foo');
        $this->assertSame('foo', $router->dispatch(Request::create('foo', 'GET'))->getContent());
    }

    public function testControllerCallActionMethodParameters()
    {
        $router = $this->getRouter();

        // Has one argument but receives two
        unset($_SERVER['__test.controller_callAction_parameters']);
        $router->get(($str = Str::random()).'/{one}/{two}', RouteTestAnotherControllerWithParameterStub::class.'@oneArgument');
        $router->dispatch(Request::create($str.'/one/two', 'GET'));
        $this->assertEquals(['one' => 'one', 'two' => 'two'], $_SERVER['__test.controller_callAction_parameters']);

        // Has two arguments and receives two
        unset($_SERVER['__test.controller_callAction_parameters']);
        $router->get(($str = Str::random()).'/{one}/{two}', RouteTestAnotherControllerWithParameterStub::class.'@twoArguments');
        $router->dispatch(Request::create($str.'/one/two', 'GET'));
        $this->assertEquals(['one' => 'one', 'two' => 'two'], $_SERVER['__test.controller_callAction_parameters']);

        // Has two arguments but with different names from the ones passed from the route
        unset($_SERVER['__test.controller_callAction_parameters']);
        $router->get(($str = Str::random()).'/{one}/{two}', RouteTestAnotherControllerWithParameterStub::class.'@differentArgumentNames');
        $router->dispatch(Request::create($str.'/one/two', 'GET'));
        $this->assertEquals(['one' => 'one', 'two' => 'two'], $_SERVER['__test.controller_callAction_parameters']);

        // Has two arguments with same name but argument order is reversed
        unset($_SERVER['__test.controller_callAction_parameters']);
        $router->get(($str = Str::random()).'/{one}/{two}', RouteTestAnotherControllerWithParameterStub::class.'@reversedArguments');
        $router->dispatch(Request::create($str.'/one/two', 'GET'));
        $this->assertEquals(['one' => 'one', 'two' => 'two'], $_SERVER['__test.controller_callAction_parameters']);

        // No route parameters while method has parameters
        unset($_SERVER['__test.controller_callAction_parameters']);
        $router->get(($str = Str::random()).'', RouteTestAnotherControllerWithParameterStub::class.'@oneArgument');
        $router->dispatch(Request::create($str, 'GET'));
        $this->assertEquals([], $_SERVER['__test.controller_callAction_parameters']);

        // With model bindings
        unset($_SERVER['__test.controller_callAction_parameters']);
        $router->get(($str = Str::random()).'/{user}/{defaultNull?}/{team?}', [
            'middleware' => SubstituteBindings::class,
            'uses' => RouteTestAnotherControllerWithParameterStub::class.'@withModels',
        ]);
        $router->dispatch(Request::create($str.'/1', 'GET'));

        $values = array_values($_SERVER['__test.controller_callAction_parameters']);

        $this->assertInstanceOf(Request::class, $values[0]);
        $this->assertEquals(1, $values[1]->value);
        $this->assertNull($values[2]);
        $this->assertNull($values[3]);
    }

    public function testLeadingParamDoesntReceiveForwardSlashOnEmptyPath()
    {
        $router = $this->getRouter();
        $outer_one = 'abc1234'; // a string that is not one we're testing
        $router->get('{one?}', [
            'uses' => function ($one = null) use (&$outer_one) {
                $outer_one = $one;

                return $one;
            },
            'where' => ['one' => '(.+)'],
        ]);

        $this->assertSame('', $router->dispatch(Request::create(''))->getContent());
        $this->assertNull($outer_one);
        // Expects: '' ($one === null)
        // Actual: '/' ($one === '/')

        $this->assertSame('foo', $router->dispatch(Request::create('/foo', 'GET'))->getContent());
        $this->assertSame('foo/bar/baz', $router->dispatch(Request::create('/foo/bar/baz', 'GET'))->getContent());
    }

    public function testRoutesDontMatchNonMatchingPathsWithLeadingOptionals()
    {
        $this->expectException(NotFoundHttpException::class);

        $router = $this->getRouter();
        $router->get('{baz?}', function ($age = 25) {
            return $age;
        });
        $this->assertSame('25', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
    }

    public function testRoutesDontMatchNonMatchingDomain()
    {
        $this->expectException(NotFoundHttpException::class);

        $router = $this->getRouter();
        $router->get('foo/bar', ['domain' => 'api.foo.bar', function () {
            return 'hello';
        }]);
        $this->assertSame('hello', $router->dispatch(Request::create('http://api.baz.boom/foo/bar', 'GET'))->getContent());
    }

    public function testRouteDomainRegistration()
    {
        $router = $this->getRouter();
        $router->get('/foo/bar')->domain('api.foo.bar')->uses(function () {
            return 'hello';
        });
        $this->assertSame('hello', $router->dispatch(Request::create('http://api.foo.bar/foo/bar', 'GET'))->getContent());
    }

    public function testMatchesMethodAgainstRequests()
    {
        /*
         * Basic
         */
        $request = Request::create('foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', function () {
            //
        });
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/bar', 'GET');
        $route = new Route('GET', 'foo', function () {
            //
        });
        $this->assertFalse($route->matches($request));

        /*
         * Method checks
         */
        $request = Request::create('foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', function () {
            //
        });
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/bar', 'POST');
        $route = new Route('GET', 'foo', function () {
            //
        });
        $this->assertFalse($route->matches($request));

        /*
         * Domain checks
         */
        $request = Request::create('http://something.foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['domain' => '{foo}.foo.com', function () {
            //
        }]);
        $this->assertTrue($route->matches($request));

        $request = Request::create('http://something.bar.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['domain' => '{foo}.foo.com', function () {
            //
        }]);
        $this->assertFalse($route->matches($request));

        /*
         * HTTPS checks
         */
        $request = Request::create('https://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['https', function () {
            //
        }]);
        $this->assertTrue($route->matches($request));

        $request = Request::create('https://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['https', 'baz' => true, function () {
            //
        }]);
        $this->assertTrue($route->matches($request));

        $request = Request::create('http://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['https', function () {
            //
        }]);
        $this->assertFalse($route->matches($request));

        /*
         * HTTP checks
         */
        $request = Request::create('https://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['http', function () {
            //
        }]);
        $this->assertFalse($route->matches($request));

        $request = Request::create('http://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['http', function () {
            //
        }]);
        $this->assertTrue($route->matches($request));

        $request = Request::create('http://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['baz' => true, function () {
            //
        }]);
        $this->assertTrue($route->matches($request));
    }

    public function testWherePatternsProperlyFilter()
    {
        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar}', function () {
            //
        });
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123abc', 'GET');
        $route = new Route('GET', 'foo/{bar}', function () {
            //
        });
        $route->where('bar', '[0-9]+');
        $this->assertFalse($route->matches($request));

        $request = Request::create('foo/123abc', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['where' => ['bar' => '[0-9]+'], function () {
            //
        }]);
        $route->where('bar', '[0-9]+');
        $this->assertFalse($route->matches($request));

        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['where' => ['bar' => '123|456'], function () {
            //
        }]);
        $route->where('bar', '123|456');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123abc', 'GET');
        $route = new Route('GET', 'foo/{bar}', ['where' => ['bar' => '123|456'], function () {
            //
        }]);
        $route->where('bar', '123|456');
        $this->assertFalse($route->matches($request));

        /*
         * Optional
         */
        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar?}', function () {
            //
        });
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar?}', ['where' => ['bar' => '[0-9]+'], function () {
            //
        }]);
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123', 'GET');
        $route = new Route('GET', 'foo/{bar?}/{baz?}', function () {
            //
        });
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123/foo', 'GET');
        $route = new Route('GET', 'foo/{bar?}/{baz?}', function () {
            //
        });
        $route->where('bar', '[0-9]+');
        $this->assertTrue($route->matches($request));

        $request = Request::create('foo/123abc', 'GET');
        $route = new Route('GET', 'foo/{bar?}', function () {
            //
        });
        $route->where('bar', '[0-9]+');
        $this->assertFalse($route->matches($request));
    }

    public function testRoutePrefixParameterParsing()
    {
        $route = new Route('GET', '/foo', ['prefix' => 'profiles/{user:username}/portfolios', 'uses' => function () {
            //
        }]);

        $this->assertSame('profiles/{user}/portfolios/foo', $route->uri());
    }

    public function testDotDoesNotMatchEverything()
    {
        $route = new Route('GET', 'images/{id}.{ext}', function () {
            //
        });

        $request1 = Request::create('images/1.png', 'GET');
        $this->assertTrue($route->matches($request1));
        $route->bind($request1);
        $this->assertSame('1', (string) $route->parameter('id'));
        $this->assertSame('png', $route->parameter('ext'));

        $request2 = Request::create('images/12.png', 'GET');
        $this->assertTrue($route->matches($request2));
        $route->bind($request2);
        $this->assertSame('12', $route->parameter('id'));
        $this->assertSame('png', $route->parameter('ext'));
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
        $this->assertSame('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testRouteClassBinding()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->bind('bar', RouteBindingStub::class);
        $this->assertSame('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testRouteClassMethodBinding()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->bind('bar', RouteBindingStub::class.'@find');
        $this->assertSame('dragon', $router->dispatch(Request::create('foo/Dragon', 'GET'))->getContent());
    }

    public function testMiddlewarePrioritySorting()
    {
        $middleware = [
            Placeholder1::class,
            SubstituteBindings::class,
            Placeholder2::class,
            Authenticate::class,
            ExampleMiddleware::class,
            Placeholder3::class,
        ];

        $router = $this->getRouter();

        $router->middlewarePriority = [ExampleMiddlewareContract::class, Authenticate::class, SubstituteBindings::class, Authorize::class];

        $route = $router->get('foo', ['middleware' => $middleware, 'uses' => function ($name) {
            return $name;
        }]);

        $this->assertEquals([
            Placeholder1::class,
            ExampleMiddleware::class,
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
        $router->model('bar', RouteModelBindingStub::class);
        $this->assertSame('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testModelBindingWithNullReturn()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [Illuminate\Tests\Routing\RouteModelBindingNullStub].');

        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', RouteModelBindingNullStub::class);
        $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent();
    }

    public function testModelBindingWithCustomNullReturn()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', RouteModelBindingNullStub::class, function () {
            return 'missing';
        });
        $this->assertSame('missing', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testModelBindingWithBindingClosure()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', RouteModelBindingNullStub::class, function ($value) {
            return (new RouteModelBindingClosureStub)->findAlternate($value);
        });
        $this->assertSame('tayloralt', $router->dispatch(Request::create('foo/TAYLOR', 'GET'))->getContent());
    }

    public function testModelBindingWithCompoundParameterName()
    {
        $router = $this->getRouter();
        $router->resource('foo-bar', RouteTestResourceControllerWithModelParameter::class, ['middleware' => SubstituteBindings::class]);
        $this->assertSame('12345', $router->dispatch(Request::create('foo-bar/12345', 'GET'))->getContent());
    }

    public function testModelBindingWithCompoundParameterNameAndRouteBinding()
    {
        $router = $this->getRouter();
        $router->model('foo_bar', RoutingTestUserModel::class);
        $router->resource('foo-bar', RouteTestResourceControllerWithModelParameter::class, ['middleware' => SubstituteBindings::class]);
        $this->assertSame('12345', $router->dispatch(Request::create('foo-bar/12345', 'GET'))->getContent());
    }

    public function testModelBindingThroughIOC()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));
        $container->bind(RouteModelInterface::class, RouteModelBindingStub::class);
        $router->get('foo/{bar}', ['middleware' => SubstituteBindings::class, 'uses' => function ($name) {
            return $name;
        }]);
        $router->model('bar', RouteModelInterface::class);
        $this->assertSame('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
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
        $this->assertSame('foo', $routes[0]->getPrefix());
    }

    public function testRouteGroupingOutsideOfInheritedNamespace()
    {
        $router = $this->getRouter();

        $router->group(['namespace' => 'App\Http\Controllers'], function ($router) {
            $router->group(['namespace' => '\Foo\Bar'], function ($router) {
                $router->get('users', 'UsersController@index');
            });
        });

        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame(
            'Foo\Bar\UsersController@index',
            $routes[0]->getAction()['uses']
        );
    }

    public function testCurrentRouteUses()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', ['as' => 'foo.bar', 'uses' => RouteTestControllerStub::class.'@index']);

        $this->assertNull($router->currentRouteAction());

        $this->assertSame('Hello World', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertTrue($router->uses('*RouteTestControllerStub*'));
        $this->assertTrue($router->uses('*RouteTestControllerStub@index'));
        $this->assertTrue($router->uses(['*RouteTestControllerStub*', '*FooController*']));
        $this->assertTrue($router->uses(['*BarController*', '*FooController*', '*RouteTestControllerStub@index']));
        $this->assertTrue($router->uses(['*BarController*', '*FooController*'], '*RouteTestControllerStub*'));
        $this->assertFalse($router->uses(['*BarController*', '*FooController*']));

        $this->assertEquals($router->currentRouteAction(), RouteTestControllerStub::class.'@index');
        $this->assertTrue($router->currentRouteUses(RouteTestControllerStub::class.'@index'));
    }

    public function testRouteGroupingFromFile()
    {
        $router = $this->getRouter();
        $router->group(['prefix' => 'api'], __DIR__.'/fixtures/routes.php');

        $route = last($router->getRoutes()->get());
        $request = Request::create('api/users', 'GET');

        $this->assertTrue($route->matches($request));
        $this->assertSame('all-users', $route->bind($request)->run($request));
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
        $this->assertSame('foo/bar', $route->uri());
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
        $this->assertSame('foo/bar/baz', $route->uri());

        /*
         * nested with layer skipped
         */
        $router = $this->getRouter();
        $router->group(['prefix' => 'foo', 'as' => 'Foo::'], function () use ($router) {
            $router->group(['prefix' => 'bar'], function () use ($router) {
                $router->prefix('foz')->get('baz', ['as' => 'baz', function () {
                    return 'hello';
                }]);
            });
        });
        $routes = $router->getRoutes();
        $route = $routes->getByName('Foo::baz');
        $this->assertSame('foz/foo/bar/baz', $route->uri());
    }

    public function testNestedRouteGroupingPrefixing()
    {
        /*
         * nested with layer skipped
         */
        $router = $this->getRouter();
        $router->group(['prefix' => 'foo', 'as' => 'Foo::'], function () use ($router) {
            $router->prefix('bar')->get('baz', ['as' => 'baz', function () {
                return 'hello';
            }]);
        });
        $routes = $router->getRoutes();
        $route = $routes->getByName('Foo::baz');
        $this->assertSame('bar/foo', $route->getAction('prefix'));
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
        $this->assertSame('prefix/foo/bar', $routes[0]->uri());

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
        $this->assertSame('foo/bar', $routes[0]->uri());

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
        $this->assertSame('prefix', $routes[0]->uri());

        /*
         * Prefix homepage with empty prefix
         */
        $router = $this->getRouter();
        $router->get('/', function () {
            return 'hello';
        });
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();
        $routes[0]->prefix('/');
        $this->assertSame('/', $routes[0]->uri());
    }

    public function testRoutePreservingOriginalParametersState()
    {
        $router = $this->getRouter();
        $router->bind('bar', function ($value) {
            return strlen($value);
        });
        $router->get('foo/{bar}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function ($bar) use ($router) {
                $route = $router->getCurrentRoute();

                $this->assertSame('taylor', $route->originalParameter('bar'));
                $this->assertSame('default', $route->originalParameter('unexisting', 'default'));
                $this->assertEquals(['bar' => 'taylor'], $route->originalParameters());

                return $bar;
            },
        ]);

        $this->assertEquals(6, $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testMergingControllerUses()
    {
        $router = $this->getRouter();
        $router->group(['namespace' => 'Namespace'], function () use ($router) {
            $router->get('foo/bar', 'Controller@action');
        });
        $routes = $router->getRoutes()->getRoutes();
        $action = $routes[0]->getAction();

        $this->assertSame('Namespace\\Controller@action', $action['controller']);

        $router = $this->getRouter();
        $router->group(['namespace' => 'Namespace'], function () use ($router) {
            $router->group(['namespace' => 'Nested'], function () use ($router) {
                $router->get('foo/bar', 'Controller@action');
            });
        });
        $routes = $router->getRoutes()->getRoutes();
        $action = $routes[0]->getAction();

        $this->assertSame('Namespace\\Nested\\Controller@action', $action['controller']);

        $router = $this->getRouter();
        $router->group(['prefix' => 'baz'], function () use ($router) {
            $router->group(['namespace' => 'Namespace'], function () use ($router) {
                $router->get('foo/bar', 'Controller@action');
            });
        });
        $routes = $router->getRoutes()->getRoutes();
        $action = $routes[0]->getAction();

        $this->assertSame('Namespace\\Controller@action', $action['controller']);
    }

    public function testInvalidActionException()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid route action: [Illuminate\Tests\Routing\RouteTestControllerStub].');

        $router = $this->getRouter();
        $router->get('/', ['uses' => RouteTestControllerStub::class]);

        $router->dispatch(Request::create('/'));
    }

    public function testShallowResourceRouting()
    {
        $router = $this->getRouter();
        $router->resource('foo.bar', 'FooController', ['shallow' => true]);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('foo/{foo}/bar', $routes[0]->uri());
        $this->assertSame('foo/{foo}/bar/create', $routes[1]->uri());
        $this->assertSame('foo/{foo}/bar', $routes[2]->uri());

        $this->assertSame('bar/{bar}', $routes[3]->uri());
        $this->assertSame('bar/{bar}/edit', $routes[4]->uri());
        $this->assertSame('bar/{bar}', $routes[5]->uri());
        $this->assertSame('bar/{bar}', $routes[6]->uri());

        $router = $this->getRouter();
        $router->resource('foo', 'FooController');
        $router->resource('foo.bar.baz', 'FooController', ['shallow' => true]);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('foo', $routes[0]->uri());
        $this->assertSame('foo/create', $routes[1]->uri());
        $this->assertSame('foo', $routes[2]->uri());
        $this->assertSame('foo/{foo}', $routes[3]->uri());
        $this->assertSame('foo/{foo}/edit', $routes[4]->uri());
        $this->assertSame('foo/{foo}', $routes[5]->uri());
        $this->assertSame('foo/{foo}', $routes[6]->uri());

        $this->assertSame('foo/{foo}/bar/{bar}/baz', $routes[7]->uri());
        $this->assertSame('foo/{foo}/bar/{bar}/baz/create', $routes[8]->uri());
        $this->assertSame('foo/{foo}/bar/{bar}/baz', $routes[9]->uri());
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

        $this->assertSame('foo-bars/{foo_bar}', $routes[0]->uri());

        $router = $this->getRouter();
        $router->resource('foo-bar.foo-baz', 'FooController', ['only' => ['show']]);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('foo-bar/{foo_bar}/foo-baz/{foo_baz}', $routes[0]->uri());

        $router = $this->getRouter();
        $router->resource('foo-bars', 'FooController', ['only' => ['show'], 'as' => 'prefix']);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('foo-bars/{foo_bar}', $routes[0]->uri());
        $this->assertSame('prefix.foo-bars.show', $routes[0]->getName());

        ResourceRegistrar::verbs([
            'create' => 'ajouter',
            'edit' => 'modifier',
        ]);
        $router = $this->getRouter();
        $router->resource('foo', 'FooController');
        $routes = $router->getRoutes();

        $this->assertSame('foo/ajouter', $routes->getByName('foo.create')->uri());
        $this->assertSame('foo/{foo}/modifier', $routes->getByName('foo.edit')->uri());
    }

    public function testResourceRoutingParameters()
    {
        ResourceRegistrar::singularParameters();

        $router = $this->getRouter();
        $router->resource('foos', 'FooController');
        $router->resource('foos.bars', 'FooController');
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('foos/{foo}', $routes[3]->uri());
        $this->assertSame('foos/{foo}/bars/{bar}', $routes[10]->uri());

        ResourceRegistrar::setParameters(['foos' => 'oof', 'bazs' => 'b']);

        $router = $this->getRouter();
        $router->resource('bars.foos.bazs', 'FooController');
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('bars/{bar}/foos/{oof}/bazs/{b}', $routes[3]->uri());

        ResourceRegistrar::setParameters();
        ResourceRegistrar::singularParameters(false);

        $router = $this->getRouter();
        $router->resource('foos', 'FooController', ['parameters' => 'singular']);
        $router->resource('foos.bars', 'FooController')->parameters('singular');
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('foos/{foo}', $routes[3]->uri());
        $this->assertSame('foos/{foo}/bars/{bar}', $routes[10]->uri());

        $router = $this->getRouter();
        $router->resource('foos.bars', 'FooController', ['parameters' => ['foos' => 'foo', 'bars' => 'bar']]);
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('foos/{foo}/bars/{bar}', $routes[3]->uri());

        $router = $this->getRouter();
        $router->resource('foos.bars', 'FooController')->parameter('foos', 'foo')->parameter('bars', 'bar');
        $routes = $router->getRoutes();
        $routes = $routes->getRoutes();

        $this->assertSame('foos/{foo}/bars/{bar}', $routes[3]->uri());
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
        $container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        $request = Request::create('http://foo.com/foo/bar', 'GET');
        $route = new Route('GET', 'foo/bar', ['http', function () {
            //
        }]);

        $_SERVER['__router.request'] = null;
        $_SERVER['__router.route'] = null;

        $router->matched(function ($event) {
            $_SERVER['__router.request'] = $event->request;
            $_SERVER['__router.route'] = $event->route;
        });

        $router->dispatchToRoute($request);

        $this->assertInstanceOf(Request::class, $_SERVER['__router.request']);
        $this->assertEquals($_SERVER['__router.request'], $request);
        unset($_SERVER['__router.request']);

        $this->assertInstanceOf(Route::class, $_SERVER['__router.route']);
        $this->assertEquals($_SERVER['__router.route']->uri(), $route->uri());
        unset($_SERVER['__router.route']);
    }

    public function testRouterFiresRouteMatchingEvent()
    {
        $container = new Container;
        $router = new Router($events = new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));
        $router->get('foo/bar', function () {
            return '';
        });

        $request = Request::create('http://foo.com/foo/bar', 'GET');

        $_SERVER['__router.request'] = null;

        $events->listen(Routing::class, function ($event) {
            $_SERVER['__router.request'] = $event->request;
        });

        $router->dispatchToRoute($request);

        $this->assertInstanceOf(Request::class, $_SERVER['__router.request']);
        $this->assertEquals($_SERVER['__router.request'], $request);
        unset($_SERVER['__router.request']);
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

        $router->get('foo/bar', RouteTestControllerStub::class.'@index');

        $this->assertSame('Hello World', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertTrue($_SERVER['route.test.controller.middleware']);
        $this->assertEquals(Response::class, $_SERVER['route.test.controller.middleware.class']);
        $this->assertEquals(0, $_SERVER['route.test.controller.middleware.parameters.one']);
        $this->assertEquals(['foo', 'bar'], $_SERVER['route.test.controller.middleware.parameters.two']);
        $this->assertFalse(isset($_SERVER['route.test.controller.except.middleware']));
    }

    public function testControllerRoutingArrayCallable()
    {
        unset(
            $_SERVER['route.test.controller.middleware'], $_SERVER['route.test.controller.except.middleware'],
            $_SERVER['route.test.controller.middleware.class'],
            $_SERVER['route.test.controller.middleware.parameters.one'], $_SERVER['route.test.controller.middleware.parameters.two']
        );

        $router = $this->getRouter();

        $router->get('foo/bar', [RouteTestControllerStub::class, 'index']);

        $this->assertSame('Hello World', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertTrue($_SERVER['route.test.controller.middleware']);
        $this->assertEquals(Response::class, $_SERVER['route.test.controller.middleware.class']);
        $this->assertEquals(0, $_SERVER['route.test.controller.middleware.parameters.one']);
        $this->assertEquals(['foo', 'bar'], $_SERVER['route.test.controller.middleware.parameters.two']);
        $this->assertFalse(isset($_SERVER['route.test.controller.except.middleware']));
        $action = $router->getRoutes()->getRoutes()[0]->getAction()['controller'];
        $this->assertEquals(RouteTestControllerStub::class.'@index', $action);
    }

    public function testCallableControllerRouting()
    {
        $router = $this->getRouter();

        $router->get('foo/bar', RouteTestControllerCallableStub::class.'@bar');
        $router->get('foo/baz', RouteTestControllerCallableStub::class.'@baz');

        $this->assertSame('bar', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertSame('baz', $router->dispatch(Request::create('foo/baz', 'GET'))->getContent());
    }

    public function testControllerMiddlewareGroups()
    {
        unset(
            $_SERVER['route.test.controller.middleware'],
            $_SERVER['route.test.controller.middleware.class']
        );

        $router = $this->getRouter();

        $router->middlewareGroup('web', [
            RouteTestControllerMiddleware::class,
            RouteTestControllerMiddlewareTwo::class,
        ]);

        $router->get('foo/bar', RouteTestControllerMiddlewareGroupStub::class.'@index');

        $this->assertSame('caught', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
        $this->assertTrue($_SERVER['route.test.controller.middleware']);
        $this->assertEquals(Response::class, $_SERVER['route.test.controller.middleware.class']);
    }

    public function testImplicitBindings()
    {
        $router = $this->getRouter();

        $router->get('foo/{bar}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar) {
                $this->assertInstanceOf(RoutingTestUserModel::class, $bar);

                return $bar->value;
            },
        ]);

        $this->assertSame('taylor', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testImplicitBindingsWithClosure()
    {
        $router = $this->getRouter();

        $router->substituteImplicitBindingsUsing(function ($container, $route, $default) {
            $default = $default();

            $model = $route->parameter('bar');
            $model->value = 'otwell';
        });

        $router->get('foo/{bar}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar) {
                return $bar->value;
            },
        ]);

        $this->assertSame('otwell', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testImplicitBindingsWhereScopedBindingsArePrevented()
    {
        $router = $this->getRouter();

        $router->get('foo/{test_team}/{user:id}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestTeamWithoutUserModel $testTeam, RoutingTestUserModel $user) {
                $this->assertInstanceOf(RoutingTestTeamWithoutUserModel::class, $testTeam);
                $this->assertInstanceOf(RoutingTestUserModel::class, $user);

                return $testTeam->value.'|'.$user->value;
            },
        ])->withoutScopedBindings();

        $this->assertSame('1|4', $router->dispatch(Request::create('foo/1/4', 'GET'))->getContent());
    }

    public function testParentChildImplicitBindings()
    {
        $router = $this->getRouter();

        $router->get('foo/{user}/{post:slug}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $user, RoutingTestPostModel $post) {
                $this->assertInstanceOf(RoutingTestUserModel::class, $user);
                $this->assertInstanceOf(RoutingTestPostModel::class, $post);

                return $user->value.'|'.$post->value;
            },
        ]);

        $this->assertSame('1|test-slug', $router->dispatch(Request::create('foo/1/test-slug', 'GET'))->getContent());
    }

    public function testParentChildImplicitBindingsWhereOnlySomeParametersAreScoped()
    {
        $router = $this->getRouter();
        $action = function (RoutingTestTeamModel $team, RoutingTestUserModel $user, RoutingTestPostModel $post) {
            $this->assertInstanceOf(RoutingTestTeamModel::class, $team);
            $this->assertInstanceOf(RoutingTestUserModel::class, $user);
            $this->assertInstanceOf(RoutingTestPostModel::class, $post);

            return $team->value.'|'.$user->value.'|'.$post->value;
        };

        $router->get('foo/{team}/{user:slug}/{post}', [
            'middleware' => SubstituteBindings::class,
            'uses' => $action,
        ]);
        $this->assertSame('1|test-slug|2', $router->dispatch(Request::create('foo/1/test-slug/2', 'GET'))->getContent());

        $router->get('foo/{team}/{user}/{post:id}', [
            'middleware' => SubstituteBindings::class,
            'uses' => $action,
        ]);
        $this->assertSame('2|another-test-slug|3', $router->dispatch(Request::create('foo/2/another-test-slug/3', 'GET'))->getContent());
    }

    public function testApiResourceScopingWhenChildDoesNotBelongToParent()
    {
        ResourceRegistrar::singularParameters();
        $router = $this->getRouter();
        $router->apiResource(
            'teams.users',
            RouteTestNestedResourceControllerWithMissingUser::class,
            ['only' => ['show']],
        )
            ->middleware(SubstituteBindings::class)
            ->scoped();

        $this->expectException(ModelNotFoundException::class);

        $router->dispatch(Request::create('teams/1/users/2', 'GET'));
    }

    public function testParentChildImplicitBindingsProperlyCamelCased()
    {
        $router = $this->getRouter();

        $router->get('foo/{user}/{test_team:id}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $user, RoutingTestTeamModel $testTeam) {
                $this->assertInstanceOf(RoutingTestUserModel::class, $user);
                $this->assertInstanceOf(RoutingTestTeamModel::class, $testTeam);

                return $user->value.'|'.$testTeam->value;
            },
        ]);

        $this->assertSame('1|4', $router->dispatch(Request::create('foo/1/4', 'GET'))->getContent());
    }

    public function testImplicitBindingsWithOptionalParameterWithExistingKeyInUri()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar?}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar = null) {
                $this->assertInstanceOf(RoutingTestUserModel::class, $bar);

                return $bar->value;
            },
        ]);
        $this->assertSame('taylor', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
    }

    public function testImplicitBindingsWithMissingModelHandledByMissing()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RouteModelBindingNullStub $bar = null) {
                $this->assertInstanceOf(RouteModelBindingNullStub::class, $bar);

                return $bar->first();
            },
        ])->missing(function () {
            return new RedirectResponse('/', 302);
        });

        $request = Request::create('foo/taylor', 'GET');

        $response = $router->dispatch($request);
        $this->assertTrue($response->isRedirect('/'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testImplicitBindingsWithMissingModelHandledByMissingOnGroupLevel()
    {
        $router = $this->getRouter();
        $router->as('foo.')
            ->missing(fn () => new RedirectResponse('/', 302))
            ->group(function () use ($router) {
                $router->get('foo/{bar}', [
                    'middleware' => SubstituteBindings::class,
                    'uses' => function (RouteModelBindingNullStub $bar = null) {
                        $this->assertInstanceOf(RouteModelBindingNullStub::class, $bar);

                        return $bar->first();
                    },
                ]);
            });

        $request = Request::create('foo/taylor', 'GET');

        $response = $router->dispatch($request);
        $this->assertTrue($response->isRedirect('/'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testImplicitBindingsWithOptionalParameterWithNoKeyInUri()
    {
        $router = $this->getRouter();
        $router->get('foo/{bar?}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar = null) {
                $this->assertNull($bar);
            },
        ]);
        $router->dispatch(Request::create('foo', 'GET'))->getContent();
    }

    public function testImplicitBindingsWithOptionalParameterUsingEnumIsAlwaysCastedToEnum()
    {
        include_once 'Enums.php';

        $router = $this->getRouter();
        $router->get('foo/{bar?}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (?\Illuminate\Tests\Routing\CategoryBackedEnum $bar = null) {
                $this->assertInstanceOf(CategoryBackedEnum::class, $bar);
            },
        ]);
        $router->dispatch(Request::create('foo/people', 'GET'))->getContent();
    }

    public function testImplicitBindingsWithOptionalParameterWithNonExistingKeyInUri()
    {
        $this->expectException(ModelNotFoundException::class);

        $router = $this->getRouter();
        $router->get('foo/{bar?}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestNonExistingUserModel $bar = null) {
                $this->fail('ModelNotFoundException was expected.');
            },
        ]);
        $router->dispatch(Request::create('foo/nonexisting', 'GET'))->getContent();
    }

    public function testImplicitBindingThroughIOC()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        $container->bind(RoutingTestUserModel::class, RoutingTestExtendedUserModel::class);
        $router->get('foo/{bar}', [
            'middleware' => SubstituteBindings::class,
            'uses' => function (RoutingTestUserModel $bar) {
                $this->assertInstanceOf(RoutingTestExtendedUserModel::class, $bar);
            },
        ]);
        $router->dispatch(Request::create('foo/baz', 'GET'))->getContent();
    }

    public function testDispatchingCallableActionClasses()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', ActionStub::class);

        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

        $router->get('foo/bar2', [
            'uses' => ActionStub::class,
        ]);

        $this->assertSame('hello', $router->dispatch(Request::create('foo/bar2', 'GET'))->getContent());
    }

    public function testResponseIsReturned()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return 'hello';
        });

        $response = $router->dispatch(Request::create('foo/bar', 'GET'));
        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotInstanceOf(JsonResponse::class, $response);
    }

    public function testJsonResponseIsReturned()
    {
        $router = $this->getRouter();
        $router->get('foo/bar', function () {
            return ['foo', 'bar'];
        });

        $response = $router->dispatch(Request::create('foo/bar', 'GET'));
        $this->assertNotInstanceOf(Response::class, $response);
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testRouteFlushController()
    {
        $container = new Container;
        $router = $this->getRouter();

        $router->get('count', ActionCountStub::class);
        $request = Request::create('count', 'GET');

        $response = $router->dispatch($request);
        $this->assertSame(1, $response->original['invokedCount']);
        $this->assertSame(1, $response->original['middlewareInvokedCount']);

        $response = $router->dispatch($request);
        $this->assertSame(2, $response->original['invokedCount']);
        $this->assertSame(2, $response->original['middlewareInvokedCount']);

        $request->route()->flushController();
        $response = $router->dispatch($request);
        $this->assertSame(1, $response->original['invokedCount']);
        $this->assertSame(1, $response->original['middlewareInvokedCount']);
    }

    public function testRouteRedirect()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $request = Request::create('contact_us', 'GET');
        $container->singleton(Request::class, function () use ($request) {
            return $request;
        });
        $urlGenerator = new UrlGenerator(new RouteCollection, $request);
        $container->singleton(UrlGenerator::class, function () use ($urlGenerator) {
            return $urlGenerator;
        });
        $router->get('contact_us', function () {
            throw new Exception('Route should not be reachable.');
        });
        $router->redirect('contact_us', 'contact');

        $response = $router->dispatch($request);
        $this->assertTrue($response->isRedirect('contact'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRouteRedirectRetainsExistingStartingForwardSlash()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $request = Request::create('contact_us', 'GET');
        $container->singleton(Request::class, function () use ($request) {
            return $request;
        });
        $urlGenerator = new UrlGenerator(new RouteCollection, $request);
        $container->singleton(UrlGenerator::class, function () use ($urlGenerator) {
            return $urlGenerator;
        });
        $router->get('contact_us', function () {
            throw new Exception('Route should not be reachable.');
        });
        $router->redirect('contact_us', '/contact');

        $response = $router->dispatch($request);
        $this->assertTrue($response->isRedirect('/contact'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRouteRedirectStripsMissingStartingForwardSlash()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $request = Request::create('contact_us', 'GET');
        $container->singleton(Request::class, function () use ($request) {
            return $request;
        });
        $urlGenerator = new UrlGenerator(new RouteCollection, $request);
        $container->singleton(UrlGenerator::class, function () use ($urlGenerator) {
            return $urlGenerator;
        });
        $router->get('contact_us', function () {
            throw new Exception('Route should not be reachable.');
        });
        $router->redirect('contact_us', 'contact');

        $response = $router->dispatch($request);
        $this->assertTrue($response->isRedirect('contact'));
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testRouteRedirectExceptionWhenMissingExpectedParameters()
    {
        $this->expectException(UrlGenerationException::class);
        $this->expectExceptionMessage('Missing required parameter for [Route: laravel_route_redirect_destination] [URI: users/{user}] [Missing parameter: user].');

        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $request = Request::create('users', 'GET');
        $container->singleton(Request::class, function () use ($request) {
            return $request;
        });
        $urlGenerator = new UrlGenerator(new RouteCollection, $request);
        $container->singleton(UrlGenerator::class, function () use ($urlGenerator) {
            return $urlGenerator;
        });
        $router->get('users', function () {
            throw new Exception('Route should not be reachable.');
        });
        $router->redirect('users', 'users/{user}');

        $router->dispatch($request);
    }

    public function testRouteRedirectWithCustomStatus()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $request = Request::create('contact_us', 'GET');
        $container->singleton(Request::class, function () use ($request) {
            return $request;
        });
        $urlGenerator = new UrlGenerator(new RouteCollection, $request);
        $container->singleton(UrlGenerator::class, function () use ($urlGenerator) {
            return $urlGenerator;
        });
        $router->get('contact_us', function () {
            throw new Exception('Route should not be reachable.');
        });
        $router->redirect('contact_us', 'contact', 301);

        $response = $router->dispatch($request);
        $this->assertTrue($response->isRedirect('contact'));
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testRoutePermanentRedirect()
    {
        $container = new Container;
        $router = new Router(new Dispatcher, $container);
        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });
        $request = Request::create('contact_us', 'GET');
        $container->singleton(Request::class, function () use ($request) {
            return $request;
        });
        $urlGenerator = new UrlGenerator(new RouteCollection, $request);
        $container->singleton(UrlGenerator::class, function () use ($urlGenerator) {
            return $urlGenerator;
        });
        $router->get('contact_us', function () {
            throw new Exception('Route should not be reachable.');
        });
        $router->permanentRedirect('contact_us', 'contact');

        $response = $router->dispatch($request);
        $this->assertTrue($response->isRedirect('contact'));
        $this->assertEquals(301, $response->getStatusCode());
    }

    public function testRouteCanMiddlewareCanBeAssigned()
    {
        $route = new Route(['GET'], '/', []);
        $route->middleware(['foo'])->can('create', Route::class);

        $this->assertEquals([
            'foo',
            'can:create,Illuminate\Routing\Route',
        ], $route->middleware());

        $route = new Route(['GET'], '/', []);
        $route->can('create');

        $this->assertEquals([
            'can:create',
        ], $route->middleware());
    }

    public function testItDispatchesEventsWhilePreparingRequest()
    {
        $events = new Dispatcher;
        $preparing = [];
        $prepared = [];
        $events->listen(PreparingResponse::class, function ($event) use (&$preparing) {
            $preparing[] = $event;
        });
        $events->listen(ResponsePrepared::class, function ($event) use (&$prepared) {
            $prepared[] = $event;
        });
        $container = new Container;
        $container->instance(Dispatcher::class, $events);
        $router = $this->getRouter($container);
        $router->get('foo/bar', function () {
            return 'hello';
        });
        $request = Request::create('foo/bar', 'GET');

        $response = $router->dispatch($request);

        $this->assertSame('hello', $response->getContent());
        $this->assertCount(2, $preparing);
        $this->assertSame($request, $preparing[0]->request);
        $this->assertSame('hello', $preparing[0]->response);
        $this->assertSame($request, $preparing[1]->request);
        $this->assertSame($response, $preparing[1]->response);
        $this->assertCount(2, $prepared);
        $this->assertSame($request, $prepared[0]->request);
        $this->assertSame($response, $prepared[0]->response);
        $this->assertSame($request, $prepared[1]->request);
        $this->assertSame($response, $prepared[1]->response);
    }

    protected function getRouter($container = null)
    {
        $container ??= new Container;

        $router = new Router($container->make(Dispatcher::class), $container);

        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });

        $container->bind(ControllerDispatcherContract::class, fn ($app) => new ControllerDispatcher($app));
        $container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        return $router;
    }
}

class RouteTestControllerStub extends Controller
{
    public function __construct()
    {
        $this->middleware(RouteTestControllerMiddleware::class);
        $this->middleware(RouteTestControllerParameterizedMiddlewareOne::class.':0');
        $this->middleware(RouteTestControllerParameterizedMiddlewareTwo::class.':foo,bar');
        $this->middleware(RouteTestControllerExceptMiddleware::class, ['except' => 'index']);
    }

    public function index()
    {
        return 'Hello World';
    }
}

class RouteTestControllerCallableStub extends Controller
{
    public function __call($method, $arguments = [])
    {
        return $method;
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

class RouteTestAnotherControllerWithParameterStub extends Controller
{
    public function callAction($method, $parameters)
    {
        $_SERVER['__test.controller_callAction_parameters'] = $parameters;
    }

    public function oneArgument($one)
    {
        //
    }

    public function twoArguments($one, $two)
    {
        //
    }

    public function differentArgumentNames($bar, $baz)
    {
        //
    }

    public function reversedArguments($two, $one)
    {
        //
    }

    public function withModels(Request $request, RoutingTestUserModel $user, $defaultNull = null, RoutingTestTeamModel $team = null)
    {
        //
    }
}

class RouteTestResourceControllerWithModelParameter extends Controller
{
    public function show(RoutingTestUserModel $fooBar)
    {
        return $fooBar->value;
    }
}

class RouteTestNestedResourceControllerWithMissingUser extends Controller
{
    public function show(RoutingTestTeamWithoutUserModel $team, RoutingTestUserModel $user)
    {
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
        return new Response('caught');
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

class ResponsableResponse implements Responsable
{
    public function toResponse($request)
    {
        return new Response('bar');
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

class RouteModelBindingStub extends Model
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

class RouteModelBindingNullStub extends Model
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
        //
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
        return new Response('caught '.$parameter);
    }
}

class RoutingTestUserModel extends Model
{
    public function posts()
    {
        return new RoutingTestPostModel;
    }

    public function testTeams()
    {
        return new RoutingTestTeamModel;
    }

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

class RoutingTestPostModel extends Model
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
}

class RoutingTestTeamModel extends Model
{
    public function users()
    {
        return new RoutingTestUserModel;
    }

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
    //
}

class RoutingTestNonExistingUserModel extends RoutingTestUserModel
{
    public function first()
    {
        //
    }

    public function firstOrFail()
    {
        throw new ModelNotFoundException;
    }
}

class RoutingTestTeamWithoutUserModel extends RoutingTestTeamModel
{
    public function users()
    {
        throw new ModelNotFoundException();
    }
}

class ActionStub
{
    public function __invoke()
    {
        return 'hello';
    }
}

class ActionCountStub extends Controller
{
    protected $middlewareInvokedCount = 0;

    protected $invokedCount = 0;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->middlewareInvokedCount++;

            return $next($request);
        });
    }

    public function __invoke()
    {
        $this->invokedCount++;

        return [
            'invokedCount' => $this->invokedCount,
            'middlewareInvokedCount' => $this->middlewareInvokedCount,
        ];
    }
}

interface ExampleMiddlewareContract
{
    //
}

class ExampleMiddleware implements ExampleMiddlewareContract
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
