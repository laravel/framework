<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Illuminate\Tests\Auth\Fixtures\AuthorizesResourcesController;

class AuthorizesResourcesTest extends TestCase
{
    public function testCreateMethod()
    {
        $controller = new AuthorizesResourcesController();

        $this->assertHasMiddleware($controller, 'create', 'can:create,App\User');
    }

    public function testStoreMethod()
    {
        $controller = new AuthorizesResourcesController();

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\User');
    }

    public function testShowMethod()
    {
        $controller = new AuthorizesResourcesController();

        $this->assertHasMiddleware($controller, 'show', 'can:view,user');
    }

    public function testEditMethod()
    {
        $controller = new AuthorizesResourcesController();

        $this->assertHasMiddleware($controller, 'edit', 'can:update,user');
    }

    public function testUpdateMethod()
    {
        $controller = new AuthorizesResourcesController();

        $this->assertHasMiddleware($controller, 'update', 'can:update,user');
    }

    public function testDestroyMethod()
    {
        $controller = new AuthorizesResourcesController();

        $this->assertHasMiddleware($controller, 'destroy', 'can:delete,user');
    }

    /**
     * Assert that the given middleware has been registered on the given controller for the given method.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @param  string  $middleware
     * @return void
     */
    protected function assertHasMiddleware($controller, $method, $middleware)
    {
        $router = new Router(new \Illuminate\Events\Dispatcher);

        $router->aliasMiddleware('can', '\Illuminate\Tests\Auth\Fixtures\AuthorizesResourcesMiddleware');
        $router->get($method)->uses('\Illuminate\Tests\Auth\Fixtures\AuthorizesResourcesController@'.$method);

        $this->assertEquals(
            'caught '.$middleware,
            $router->dispatch(Request::create($method, 'GET'))->getContent(),
            "The [{$middleware}] middleware was not registered for method [{$method}]"
        );
    }
}
