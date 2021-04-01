<?php

namespace Illuminate\Tests\Auth;

use Closure;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

class AuthorizesResourcesTest extends TestCase
{
    public function testCreateMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'create', 'can:create,App\User');
    }

    public function testStoreMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\User');
    }

    public function testShowMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'show', 'can:view,user');
    }

    public function testEditMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'edit', 'can:update,user');
    }

    public function testUpdateMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'update', 'can:update,user');
    }

    public function testDestroyMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'destroy', 'can:delete,user');
    }

    public function testAuthorizesOtherMethods() {
        $controller = new AuthorizesAllMethodsController;

        $this->assertHasMiddleware($controller, 'noBinding', 'can:noBinding,Illuminate\Tests\Auth\DummyUser');
        $this->assertHasMiddleware($controller, 'withBinding', 'can:withBinding,user');

        $this->assertCount(2, $controller->getMiddleware());
    }

    public function testAuthorizesOnlyMethods() {
        $controller = new AuthorizesOnlyMethodsController;

        $this->assertHasMiddleware($controller, 'noBinding', 'can:noBinding,Illuminate\Tests\Auth\DummyUser');
        $this->assertHasMiddleware($controller, 'withBinding', 'can:withBinding,user');

        $this->assertCount(2, $controller->getMiddleware());
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
        $router = new Router(new Dispatcher);

        $router->aliasMiddleware('can', AuthorizesResourcesMiddleware::class);
        $router->get($method)->uses([ get_class($controller), $method ]);

        $this->assertSame(
            'caught '.$middleware,
            $router->dispatch(Request::create($method, 'GET'))->getContent(),
            "The [{$middleware}] middleware was not registered for method [{$method}]"
        );
    }
}

class AuthorizesResourcesController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource('App\User', 'user');
    }

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store()
    {
        //
    }

    public function show()
    {
        //
    }

    public function edit()
    {
        //
    }

    public function update()
    {
        //
    }

    public function destroy()
    {
        //
    }
}

class AuthorizesAllMethodsController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeMethods(DummyUser::class, 'user');
    }

    public function index() {}

    public function show(DummyUser $user) {}

    public function noBinding() {}

    public function withBinding(DummyUser $user) {}
}


class AuthorizesOnlyMethodsController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeOnly(DummyUser::class, [ 'noBinding', 'withBinding' ], 'user');
    }

    public function noBinding() {}

    public function withBinding(DummyUser $user) {}

    public function excluded() {}
}

class DummyUser {}

class AuthorizesResourcesMiddleware
{
    public function handle($request, Closure $next, $method, $parameter)
    {
        return "caught can:{$method},{$parameter}";
    }
}
