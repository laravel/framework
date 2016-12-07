<?php

namespace Illuminate\Redis;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton('redis', function ($app) {
            $servers = $app['config']['database.redis'];

            $client = Arr::pull($servers, 'client', 'predis');

            if ($client === 'phpredis') {
                return new PhpRedisDatabase($servers);
            } else {
                return new PredisDatabase($servers);
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['redis'];
    }
}
