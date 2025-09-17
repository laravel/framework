<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\AuthManager;
use Orchestra\Testbench\TestCase;

class AuthManagerPackageDiscoveryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        
        unset($_SERVER['argv']);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Illuminate\Auth\AuthServiceProvider::class,
        ];
    }

    public function testAuthManagerDoesNotThrowErrorDuringPackageDiscovery()
    {
        $_SERVER['argv'] = ['artisan', 'package:discover'];
        
        $this->app['config']->set('auth', [
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
        ]);

        $authManager = $this->app['auth'];

        $guard = $authManager->guard('api');
        
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Guard::class, $guard);
    }

    public function testAuthManagerWorksNormallyOutsidePackageDiscovery()
    {
        
        $_SERVER['argv'] = ['artisan', 'migrate'];
        
        $this->app['config']->set('auth', [
            'defaults' => ['guard' => 'api'],
            'guards' => [
                'api' => [
                    'driver' => 'session',
                    'provider' => 'users',
                ],
            ],
            'providers' => [
                'users' => [
                    'driver' => 'eloquent',
                    'model' => \Illuminate\Tests\Auth\TestUser::class,
                ],
            ],
        ]);

        $authManager = $this->app['auth'];

        $guard = $authManager->guard('api');
        
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\Guard::class, $guard);
    }

    public function testCustomDriverRegistrationWorksAfterPackageDiscovery()
    {
        $_SERVER['argv'] = ['artisan', 'migrate'];
        
        $this->app['config']->set('auth', [
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
        ]);

        $authManager = $this->app['auth'];

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
