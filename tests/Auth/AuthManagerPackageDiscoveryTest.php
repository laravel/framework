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

    public function testAuthManagerDoesNotThrowErrorDuringPackageDiscovery()
    {
        $_SERVER['argv'] = ['artisan', 'package:discover'];

        $app = new Application(__DIR__);

        // Set up minimal configuration
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

        // Register minimal services
        $app->singleton('hash', function ($app) {
            return new \Illuminate\Hashing\HashManager($app);
        });

        $app->singleton('session', function ($app) {
            return new \Illuminate\Session\SessionManager($app);
        });

        $app->singleton('events', function ($app) {
            return new \Illuminate\Events\Dispatcher();
        });

        $authManager = new AuthManager($app);

        // This should not throw an error during package discovery
        $guard = $authManager->guard('api');

        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Guard::class, $guard);
    }
}

class TestUser extends \Illuminate\Foundation\Auth\User
{
    protected $fillable = ['name', 'email', 'password'];
}