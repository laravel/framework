<?php

namespace Illuminate\Hashing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

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
        $this->app->singleton('hash', function (Application $app) {
            return new HashManager($app);
        });

        $this->app->singleton('hash.driver', function (Application $app) {
            return $app->make('hash')->driver();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['hash', 'hash.driver'];
    }
}
