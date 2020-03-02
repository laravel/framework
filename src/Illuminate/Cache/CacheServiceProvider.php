<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class CacheServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cache', static function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', static function ($app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('cache.psr6', static function ($app) {
            return new Psr16Adapter($app['cache.store'] );
        });

        $this->app->singleton('memcached.connector', static function () {
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
            'cache', 'cache.store', 'cache.psr6', 'memcached.connector',
        ];
    }
}
