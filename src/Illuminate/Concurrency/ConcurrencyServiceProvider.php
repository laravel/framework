<?php

namespace Illuminate\Concurrency;

use Illuminate\Concurrency\Console\RedisProcessorCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ConcurrencyServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ConcurrencyManager::class, function ($app) {
            return new ConcurrencyManager($app);
        });

        $this->app->alias(ConcurrencyManager::class, 'concurrency');

        // Register the driver resolver for Redis concurrency driver
        $this->app->resolving(ConcurrencyManager::class, function ($manager, $app) {
            $manager->extend('redis', function ($app) {
                $config = $app['config']['concurrency.driver.redis'] ?? [];

                return new RedisDriver(
                    $app['redis'],
                    $config['connection'] ?? 'default',
                    $config['queue_prefix'] ?? 'laravel:concurrency:',
                    $config['lock_timeout'] ?? 60
                );
            });
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RedisProcessorCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['concurrency', ConcurrencyManager::class];
    }
}
