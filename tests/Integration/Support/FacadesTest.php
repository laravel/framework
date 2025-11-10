<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Illuminate\Support\Testing\Fakes\QueueFake;
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
        Auth::resolved(function (AuthManager $auth, Application $app) {
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

        Auth::resolved(function (AuthManager $auth, Application $app) {
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

    public function testRealMethod()
    {
        Notification::fake();
        $this->assertInstanceOf(NotificationFake::class, Notification::getFacadeRoot());

        Notification::real();
        $this->assertInstanceOf(ChannelManager::class, Notification::getFacadeRoot());

        Notification::fake();
        $this->assertInstanceOf(NotificationFake::class, Notification::getFacadeRoot());

        Queue::fake();
        $this->assertInstanceOf(QueueFake::class, Queue::getFacadeRoot());

        Queue::real();
        $this->assertInstanceOf(QueueManager::class, Queue::getFacadeRoot());
    }
}
