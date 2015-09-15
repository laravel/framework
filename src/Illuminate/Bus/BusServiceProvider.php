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
            return new Dispatcher($app, function ($command) use ($app) {
                if(isset($command->queue) && $command->queue instanceof QueuingConfiguration) {
                    return \Queue::connection($command->queue->connection);
                }
                return $app['Illuminate\Contracts\Queue\Queue'];
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
