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
        $this->app->singleton('Illuminate\Broadcasting\BroadcastManager', function ($app) {
            return new BroadcastManager($app);
        });

        $this->app->singleton('Illuminate\Contracts\Broadcasting\Broadcaster', function ($app) {
            return $app->make('Illuminate\Broadcasting\BroadcastManager')->connection();
        });

        $this->app->alias(
            'Illuminate\Broadcasting\BroadcastManager', 'Illuminate\Contracts\Broadcasting\Factory'
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
            'Illuminate\Broadcasting\BroadcastManager',
            'Illuminate\Contracts\Broadcasting\Factory',
            'Illuminate\Contracts\Broadcasting\Broadcaster',
        ];
    }
}
