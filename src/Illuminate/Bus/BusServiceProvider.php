<?php

namespace Illuminate\Bus;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;

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
        $this->app->singleton(Dispatcher::class, function ($app) {
            return new Dispatcher($app, function () use ($app) {
                return $app[QueueContract::class];
            });
        });

        $this->app->alias(
            Dispatcher::class, BusDispatcherContract::class
        );

        $this->app->alias(
            Dispatcher::class, QueueingDispatcher::class
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
            Dispatcher::class,
            BusDispatcherContract::class,
            QueueingDispatcher::class,
        ];
    }
}
