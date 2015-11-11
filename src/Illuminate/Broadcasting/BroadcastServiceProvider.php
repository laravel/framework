<?php

namespace Illuminate\Broadcasting;

use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Illuminate\Broadcasting\BroadcastManager::class, function ($app) {
            return new BroadcastManager($app);
        });

        $this->app->singleton(\Illuminate\Contracts\Broadcasting\Broadcaster::class, function ($app) {
            return $app->make(\Illuminate\Broadcasting\BroadcastManager::class)->connection();
        });

        $this->app->alias(
            \Illuminate\Broadcasting\BroadcastManager::class, \Illuminate\Contracts\Broadcasting\Factory::class
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
            \Illuminate\Broadcasting\BroadcastManager::class,
            \Illuminate\Contracts\Broadcasting\Factory::class,
            \Illuminate\Contracts\Broadcasting\Broadcaster::class,
        ];
    }
}
