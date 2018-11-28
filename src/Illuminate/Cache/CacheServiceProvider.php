<?php

namespace Illuminate\Cache;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class CacheServiceProvider extends ServiceProvider
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
        $this->app->singleton('cache', function (Application $app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', function (Application $app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('memcached.connector', function () {
            return new MemcachedConnector;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'cache', 'cache.store', 'memcached.connector',
        ];
    }
}
