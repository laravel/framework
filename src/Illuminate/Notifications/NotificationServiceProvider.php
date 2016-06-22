<?php

namespace Illuminate\Notifications;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Notifications\Factory as FactoryContract;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Boot the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'notifications');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/resources/views' => resource_path('views/vendor/notifications'),
            ], 'laravel-notifications');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ChannelManager::class, function ($app) {
            return new ChannelManager($app);
        });

        $this->app->alias(
            ChannelManager::class, FactoryContract::class
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
            ChannelManager::class, FactoryContract::class,
        ];
    }
}
