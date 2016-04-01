<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;

class AuthorizesResourcesTest extends PHPUnit_Framework_TestCase
{
    public function testIndexMethod()
    {
        $controller = new AuthorizesResourcesController($this->request('index'));

        $this->assertHasMiddleware($controller, 'can:view,App\User');
    }

    public function testCreateMethod()
    {
        $controller = new AuthorizesResourcesController($this->request('create'));

        $this->assertHasMiddleware($controller, 'can:create,App\User');
    }

    public function testStoreMethod()
    {
        $controller = new AuthorizesResourcesController($this->request('store'));

        $this->assertHasMiddleware($controller, 'can:create,App\User');
    }

    public function testShowMethod()
    {
        $controller = new AuthorizesResourcesController($this->request('show'));

        $this->assertHasMiddleware($controller, 'can:view,user');
    }

    public function testEditMethod()
    {
        $controller = new AuthorizesResourcesController($this->request('edit'));

        $this->assertHasMiddleware($controller, 'can:update,user');
    }

    public function testUpdateMethod()
    {
        $controller = new AuthorizesResourcesController($this->request('update'));

        $this->assertHasMiddleware($controller, 'can:update,user');
    }

    public function testDeleteMethod()
    {
        $controller = new AuthorizesResourcesController($this->request('delete'));

        $this->assertHasMiddleware($controller, 'can:delete,user');
    }

    /**
     * Assert that the given middleware has been registered on the given controller.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $middleware
     * @return void
     */
    protected function assertHasMiddleware($controller, $middleware)
    {
        $this->assertTrue(
            in_array($middleware, array_keys($controller->getMiddleware())),
            "The [{$middleware}] middleware was not registered"
        );
    }

    /**
     * Get a request object, with the route pointing to the given method on the controller.
     *
     * @param  string  $method
     * @return \Illuminate\Http\Request
     */
    protected function request($method)
    {
        return Request::create('foo', 'GET')->setRouteResolver(function () use ($method) {
            $action = ['uses' => 'AuthorizesResourcesController@'.$method];

            $action['controller'] = $action['uses'];

            return new Route('GET', 'foo', $action);
        });
    }
}

class AuthorizesResourcesController extends Controller
{
    use AuthorizesResources;

    public function __construct(Request $request)
    {
        $this->authorizeResource('App\User', 'user', [], $request);
    }
}
