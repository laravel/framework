<?php

namespace Illuminate\Hashing;

use Illuminate\Support\ServiceProvider;

class HashServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('hash.manager', function ($app) {
            return (new HashManager($app));
        });

        $this->app->singleton('hash', function ($app) {
            return $app['hash.manager']->driver();
        });

        $this->app->singleton('hash.bcrypt', function($app) {
            return $app['hash.manager']->driver('bcrypt');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['hash', 'hash.manager', 'hash.bcrypt'];
    }
}
