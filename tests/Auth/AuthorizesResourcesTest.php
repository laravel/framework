<?php

namespace Illuminate\Tests\Auth;

use Closure;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\Attributes\Ability;
use Illuminate\Foundation\Http\Controllers\AbilityMapper;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;

class AuthorizesResourcesTest extends TestCase
{
    public function testIndexMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'index', 'can:viewAny,App\User');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'index', 'can:viewAny,App\User,App\Post');

        $controller = new AuthorizesResourcesWithAttributesController;

        $this->assertHasMiddleware($controller, 'index', 'can:viewAny,App\User');

        AbilityMapper::discoverAbilityAttributes();
        $controller = new AuthorizesResourcesWithAttributesController;

        $this->assertHasMiddleware($controller, 'index', 'can:view_any_user,App\User');
        AbilityMapper::discoverAbilityAttributes(false);
    }

    public function testCreateMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'create', 'can:create,App\User');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'create', 'can:create,App\User,App\Post');

        $controller = new AuthorizesResourcesWithAttributesController;

        $this->assertHasMiddleware($controller, 'create', 'can:create,App\User');

        AbilityMapper::discoverAbilityAttributes();
        $controller = new AuthorizesResourcesWithAttributesController;

        $this->assertMissingMiddleware($controller, 'create');
        AbilityMapper::discoverAbilityAttributes(false);
    }

    public function testStoreMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\User');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\User,App\Post');

        $controller = new AuthorizesResourcesWithAttributesController;

        $this->assertHasMiddleware($controller, 'store', 'can:create,App\User');
    }

    public function testShowMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'show', 'can:view,user');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'show', 'can:view,user,post');

        $controller = new AuthorizesResourcesWithAttributesController;

        $this->assertHasMiddleware($controller, 'show', 'can:view,user');
    }

    public function testEditMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'edit', 'can:update,user');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'edit', 'can:update,user,post');

        $controller = new AuthorizesResourcesWithAttributesController;

        $this->assertHasMiddleware($controller, 'edit', 'can:update,user');
    }

    public function testUpdateMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'update', 'can:update,user');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'update', 'can:update,user,post');

        $controller = new AuthorizesResourcesWithAttributesController;

        $this->assertHasMiddleware($controller, 'update', 'can:update,user');
    }

    public function testDestroyMethod()
    {
        $controller = new AuthorizesResourcesController;

        $this->assertHasMiddleware($controller, 'destroy', 'can:delete,user');

        $controller = new AuthorizesResourcesWithArrayController;

        $this->assertHasMiddleware($controller, 'destroy', 'can:delete,user,post');

        $controller = new AuthorizesResourcesWithAttributesController;

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
        $router = new Router(new Dispatcher);

        $router->aliasMiddleware('can', AuthorizesResourcesMiddleware::class);
        $router->get($method)->uses(get_class($controller).'@'.$method);

        $this->assertSame(
            'caught '.$middleware,
            $router->dispatch(Request::create($method, 'GET'))->getContent(),
            "The [{$middleware}] middleware was not registered for method [{$method}]"
        );
    }

    /**
     * Assert that no middleware has been registered on the given controller for the given method.
     *
     * @param  \Illuminate\Routing\Controller  $controller
     * @param  string  $method
     * @return void
     */
    protected function assertMissingMiddleware($controller, $method)
    {
        $router = new Router(new Dispatcher);

        $router->aliasMiddleware('can', AuthorizesResourcesMiddleware::class);
        $router->get($method)->uses(get_class($controller).'@'.$method);

        $this->assertSame(
            '',
            $router->dispatch(Request::create($method, 'GET'))->getContent(),
            "Middleware was registered for method [{$method}]"
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

class AuthorizesResourcesWithAttributesController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource('App\User', 'user');
    }

    #[Ability('view_any_user')]
    public function index()
    {
        //
    }

    #[Ability(null)]
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

class AuthorizesResourcesMiddleware
{
    public function handle($request, Closure $next, $method, $parameter, ...$models)
    {
        $params = array_merge([$parameter], $models);

        return "caught can:{$method},".implode(',', $params);
    }
}
