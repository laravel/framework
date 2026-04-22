<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class FacadesTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['__laravel.authResolved']);

        parent::tearDown();
    }

    public function testFacadeResolvedCanResolveCallback()
    {
        Auth::resolved(function (AuthManager $auth, Application $app) {
            $_SERVER['__laravel.authResolved'] = true;
        });

        $this->assertArrayNotHasKey('__laravel.authResolved', $_SERVER);

        $this->app->make('auth');

        $this->assertArrayHasKey('__laravel.authResolved', $_SERVER);
    }

    public function testFacadeResolvedCanResolveCallbackAfterAccessRootHasBeenResolved()
    {
        $this->app->make('auth');

        $this->assertArrayNotHasKey('__laravel.authResolved', $_SERVER);

        Auth::resolved(function (AuthManager $auth, Application $app) {
            $_SERVER['__laravel.authResolved'] = true;
        });

        $this->assertArrayHasKey('__laravel.authResolved', $_SERVER);
    }

    public function testDefaultAliases()
    {
        $defaultAliases = Facade::defaultAliases();

        $this->assertInstanceOf(Collection::class, $defaultAliases);

        foreach ($defaultAliases as $alias => $abstract) {
            $this->assertTrue(class_exists($alias));
            $this->assertTrue(class_exists($abstract));

            $reflection = new ReflectionClass($alias);
            $this->assertSame($abstract, $reflection->getName());
        }
    }
}
