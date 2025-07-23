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

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'create', 'can:create,App\User,App\Post');
    }

    public function testSingletonCreateMethod()
    {
        $controller = new AuthorizesSingletonResourcesController;

        $this->assertHasMiddleware($controller, 'create', 'can:create,App\Profile');

        $controller = new AuthorizesSingletonResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'create', 'can:create,App\Profile,App\Settings');
    }

    public function testStoreMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\User');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\User,App\Post');
    }

    public function testSingletonStoreMethod()
    {
        $controller = new AuthorizesSingletonResourcesController;

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\Profile');

        $controller = new AuthorizesSingletonResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\Profile,App\Settings');
    }

    public function testShowMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'show', 'can:view,user');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'show', 'can:view,user,post');
    }

    public function testSingletonShowMethod()
    {
        $controller = new AuthorizesSingletonResourcesController;

        $this->assertHasMiddleware($controller, 'show', 'can:view,App\Profile');

        $controller = new AuthorizesSingletonResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'show', 'can:view,App\Profile,App\Settings');
    }

    public function testEditMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'edit', 'can:update,user');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'edit', 'can:update,user,post');
    }

    public function testSingletonEditMethod()
    {
        $controller = new AuthorizesSingletonResourcesController;

        $this->assertHasMiddleware($controller, 'edit', 'can:update,App\Profile');

        $controller = new AuthorizesSingletonResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'edit', 'can:update,App\Profile,App\Settings');
    }

    public function testUpdateMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'update', 'can:update,user');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'update', 'can:update,user,post');
    }

    public function testSingletonUpdateMethod()
    {
        $controller = new AuthorizesSingletonResourcesController;

        $this->assertHasMiddleware($controller, 'update', 'can:update,App\Profile');

        $controller = new AuthorizesSingletonResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'update', 'can:update,App\Profile,App\Settings');
    }

    public function testDestroyMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'destroy', 'can:delete,user');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'destroy', 'can:delete,user,post');
    }

    public function testSingletonDestroyMethod()
    {
        $controller = new AuthorizesSingletonResourcesController;

        $this->assertHasMiddleware($controller, 'destroy', 'can:delete,App\Profile');

        $controller = new AuthorizesSingletonResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'destroy', 'can:delete,App\Profile,App\Settings');
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
        $router->get($method)->uses(get_class($controller).'@'.$method);

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

class AuthorizesResourcesWithArrayController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(['App\User', 'App\Post'], ['user', 'post']);
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

class AuthorizesSingletonResourcesController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeSingletonResource('App\Profile');
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

class AuthorizesSingletonResourcesWithArrayController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeSingletonResource(['App\Profile', 'App\Settings']);
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

class AuthorizesResourcesMiddleware
{
    public function handle($request, Closure $next, $method, $parameter, ...$models)
    {
        $params = array_merge([$parameter], $models);

        return "caught can:{$method},".implode(',', $params);
    }
}
