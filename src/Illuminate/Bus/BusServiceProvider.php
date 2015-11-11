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
        $this->app->singleton(\Illuminate\Bus\Dispatcher::class, function ($app) {
            return new Dispatcher($app, function () use ($app) {
                return $app[\Illuminate\Contracts\Queue\Queue::class];
            });
        });

        $this->app->alias(
            \Illuminate\Bus\Dispatcher::class, \Illuminate\Contracts\Bus\Dispatcher::class
        );

        $this->app->alias(
            \Illuminate\Bus\Dispatcher::class, \Illuminate\Contracts\Bus\QueueingDispatcher::class
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
            \Illuminate\Bus\Dispatcher::class,
            \Illuminate\Contracts\Bus\Dispatcher::class,
            \Illuminate\Contracts\Bus\QueueingDispatcher::class,
        ];
    }
}
