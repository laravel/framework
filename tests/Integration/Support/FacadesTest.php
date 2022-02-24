<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class FacadesTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_SERVER['__laravel.authResolved']);
    }

    public function testFacadeResolvedCanResolveCallback()
    {
        Auth::resolved(function () {
            $_SERVER['__laravel.authResolved'] = true;
        });

        $this->assertFalse(isset($_SERVER['__laravel.authResolved']));

        $this->app->make('auth');

        $this->assertTrue(isset($_SERVER['__laravel.authResolved']));
    }

    public function testFacadeResolvedCanResolveCallbackAfterAccessRootHasBeenResolved()
    {
        $this->app->make('auth');

        $this->assertFalse(isset($_SERVER['__laravel.authResolved']));

        Auth::resolved(function () {
            $_SERVER['__laravel.authResolved'] = true;
        });

        $this->assertTrue(isset($_SERVER['__laravel.authResolved']));
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
