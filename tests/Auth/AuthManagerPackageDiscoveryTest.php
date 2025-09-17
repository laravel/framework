<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;

class AuthManagerPackageDiscoveryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset the application state
        unset($_SERVER['argv']);
    }

    protected function createApplication()
    {
        $app = new Application(__DIR__);
        
        // Set up basic configuration
        $app->singleton('config', function () {
            return new \Illuminate\Config\Repository([
                'auth' => [
                    'defaults' => ['guard' => 'api'],
                    'guards' => [
                        'api' => [
                            'driver' => 'my-custom-driver',
                            'provider' => 'users',
                        ],
                    ],
                    'providers' => [
                        'users' => [
                            'driver' => 'eloquent',
                            'model' => \Illuminate\Tests\Auth\TestUser::class,
                        ],
                    ],
                ],
            ]);
        });

        // Register basic services needed by AuthManager
        $app->singleton('hash', function ($app) {
            return new \Illuminate\Hashing\HashManager($app);
        });

        $app->singleton('session', function ($app) {
            return new \Illuminate\Session\SessionManager($app);
        });

        $app->singleton('events', function ($app) {
            return new \Illuminate\Events\Dispatcher();
        });

        $app->singleton('auth', function ($app) {
            return new AuthManager($app);
        });

        return $app;
    }

    public function testAuthManagerDoesNotThrowErrorDuringPackageDiscovery()
    {
        $_SERVER['argv'] = ['artisan', 'package:discover'];

        $app = $this->createApplication();
        $authManager = $app['auth'];

        $guard = $authManager->guard('api');

        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Guard::class, $guard);
    }

    public function testAuthManagerWorksNormallyOutsidePackageDiscovery()
    {
        $_SERVER['argv'] = ['artisan', 'migrate'];

        $app = $this->createApplication();
        $app['config']->set('auth.guards.api.driver', 'session');
        
        $authManager = $app['auth'];
        $guard = $authManager->guard('api');

        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Guard::class, $guard);
    }

    public function testCustomDriverRegistrationWorksAfterPackageDiscovery()
    {
        $_SERVER['argv'] = ['artisan', 'migrate'];

        $app = $this->createApplication();
        $authManager = $app['auth'];

        // Register a custom driver
        $authManager->extend('my-custom-driver', function ($app, $name, $config) {
            return new \Illuminate\Auth\SessionGuard($name, $app['auth']->createUserProvider($config['provider']), $app['session.store']);
        });

        $guard = $authManager->guard('api');

        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Guard::class, $guard);
    }
}

class TestUser extends \Illuminate\Foundation\Auth\User
{
    protected $fillable = ['name', 'email', 'password'];
}