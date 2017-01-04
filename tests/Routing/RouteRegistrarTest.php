<?php

namespace Illuminate\Tests\Routing;

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class RouteRegistrarTest extends TestCase
{
    /**
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    public function setUp()
    {
        parent::setUp();

        $this->router = new Router(m::mock(Dispatcher::class), Container::getInstance());
    }

    public function tearDown()
    {
        m::close();
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
                     ->get('users', 'Illuminate\Tests\Routing\RouteRegistrarControllerStub@index');

        $this->seeResponse('controller', Request::create('users', 'GET'));
        $this->seeMiddleware('controller-middleware');
    }

    public function testCanRegisterRouteWithArrayAndControllerAction()
    {
        $this->router->middleware('controller-middleware')->put('users', [
            'uses' => 'Illuminate\Tests\Routing\RouteRegistrarControllerStub@index',
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

    public function testCanRegisterGroupWithNamespace()
    {
        $this->router->namespace('App\Http\Controllers')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertEquals(
            'App\Http\Controllers\UsersController@index',
            $this->getRoute()->getAction()['uses']
        );
    }

    public function testCanRegisterGroupWithPrefix()
    {
        $this->router->prefix('api')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertEquals('api/users', $this->getRoute()->uri());
    }

    public function testCanRegisterGroupWithNamePrefix()
    {
        $this->router->name('api.')->group(function ($router) {
            $router->get('users', 'UsersController@index')->name('users');
        });

        $this->assertEquals('api.users', $this->getRoute()->getName());
    }

    public function testCanRegisterGroupWithDomain()
    {
        $this->router->domain('{account}.myapp.com')->group(function ($router) {
            $router->get('users', 'UsersController@index');
        });

        $this->assertEquals('{account}.myapp.com', $this->getRoute()->domain());
    }

    public function testCanRegisterGroupWithDomainAndNamePrefix()
    {
        $this->router->domain('{account}.myapp.com')->name('api.')->group(function ($router) {
            $router->get('users', 'UsersController@index')->name('users');
        });

        $this->assertEquals('{account}.myapp.com', $this->getRoute()->domain());
        $this->assertEquals('api.users', $this->getRoute()->getName());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testRegisteringNonApprovedAttributesThrows()
    {
        $this->router->domain('foo')->missing('bar')->group(function ($router) {
            //
        });
    }

    public function testCanRegisterResource()
    {
        $this->router->middleware('resource-middleware')
                     ->resource('users', 'Illuminate\Tests\Routing\RouteRegistrarControllerStub');

        $this->seeResponse('deleted', Request::create('users/1', 'DELETE'));
        $this->seeMiddleware('resource-middleware');
    }

    public function testCanSetRouteName()
    {
        $this->router->as('users.index')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals('users.index', $this->getRoute()->getName());
    }

    public function testCanSetRouteNameUsingNameAlias()
    {
        $this->router->name('users.index')->get('users', function () {
            return 'all-users';
        });

        $this->seeResponse('all-users', Request::create('users', 'GET'));
        $this->assertEquals('users.index', $this->getRoute()->getName());
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
