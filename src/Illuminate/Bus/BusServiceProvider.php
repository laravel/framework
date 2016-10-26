<?php

namespace Illuminate\Bus;

use Illuminate\Support\ServiceProvider;

class BusServiceProvider extends ServiceProvider
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
        $this->app->singleton('Illuminate\Bus\Dispatcher', function ($app) {
            return new Dispatcher($app, function ($connection = null) use ($app) {
                return $app['Illuminate\Contracts\Queue\Factory']->connection($connection);
            });
        });

        $this->app->alias(
            'Illuminate\Bus\Dispatcher', 'Illuminate\Contracts\Bus\Dispatcher'
        );

        $this->app->alias(
            'Illuminate\Bus\Dispatcher', 'Illuminate\Contracts\Bus\QueueingDispatcher'
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
            'Illuminate\Bus\Dispatcher',
            'Illuminate\Contracts\Bus\Dispatcher',
            'Illuminate\Contracts\Bus\QueueingDispatcher',
        ];
    }
}
