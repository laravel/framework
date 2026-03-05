<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use PHPUnit\Framework\TestCase;

class RoutingAliasTest extends TestCase
{
    public function testRegisterSingleAlias()
    {
        $router = $this->getRouter();

        $router->get('users', function () {
            return 'users';
        })->name('users.index');

        $router->alias('users.index', 'members.index');

        $this->assertSame('users.index', $router->resolveAlias('members.index'));
    }

    public function testRegisterMultipleAliases()
    {
        $router = $this->getRouter();

        $router->get('users', function () {
            return 'users';
        })->name('users.index');

        $router->alias('users.index', ['members.index', 'people.index']);

        $this->assertSame('users.index', $router->resolveAlias('members.index'));
        $this->assertSame('users.index', $router->resolveAlias('people.index'));
    }

    public function testResolveAliasReturnsNullForUnknownAlias()
    {
        $router = $this->getRouter();

        $this->assertNull($router->resolveAlias('nonexistent.alias'));
    }

    public function testCompiledRoutesRestoreAliases()
    {
        [$container, $router] = $this->getRouterWithApp();

        $router->get('users', function () {
            return 'users';
        })->name('users.index');

        $router->alias('users.index', ['members.index', 'people.index']);

        $compiled = $router->getRoutes()->compile();
        $newRouter = $this->getRouter($container);
        $container->instance('router', $newRouter);
        $container->instance(Registrar::class, $newRouter);
        $newRouter->setCompiledRoutes($compiled);

        $this->assertSame('users.index', $newRouter->resolveAlias('members.index'));
        $this->assertSame('users.index', $newRouter->resolveAlias('people.index'));
    }

    public function testPreventOverrideAliases()
    {
        [$container, $router] = $this->getRouterWithApp();

        $router->get('dashboard', function () {
            return 'dashboard';
        })->name('dashboard');

        $router->get('app', function () {
            return 'app';
        })->name('app');

        $router->alias('dashboard', ['app', 'home']);

        $routes = $router->getRoutes();
        $routes->refreshNameLookups();

        $container->instance('url', new UrlGenerator($routes, Request::create('http://www.foo.com/')));

        $this->assertSame('http://www.foo.com/app', route('app'));
        $this->assertSame(route('dashboard'), route('home'));
        $this->assertNotSame(route('dashboard'), route('app'));
    }

    protected function getRouter($container = null)
    {
        $container ??= new Container;

        $router = new Router($container->make(Dispatcher::class), $container);

        $container->instance(Registrar::class, $router);

        $container->bind(ControllerDispatcherContract::class, fn ($app) => new ControllerDispatcher($app));
        $container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        return $router;
    }

    protected function getRouterWithApp(): array
    {
        $container = new Container;

        Container::setInstance($container);

        $router = $this->getRouter($container);
        $container->instance('router', $router);

        return [$container, $router];
    }
}
