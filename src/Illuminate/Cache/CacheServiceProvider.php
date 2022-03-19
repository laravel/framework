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
        $this->app->singleton('cache', fn ($app) => new CacheManager($app));

        $this->app->singleton('cache.store', fn ($app) => $app['cache']->driver());

        $this->app->singleton('cache.psr6', fn ($app) => new Psr16Adapter($app['cache.store']));

        $this->app->singleton('memcached.connector', fn () => new MemcachedConnector);

        $this->app->singleton(RateLimiter::class,
            fn ($app) => new RateLimiter($app->make('cache')->driver(
                $app['config']->get('cache.limiter'))
            )
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'cache', 'cache.store', 'cache.psr6', 'memcached.connector', RateLimiter::class,
        ];
    }
}
