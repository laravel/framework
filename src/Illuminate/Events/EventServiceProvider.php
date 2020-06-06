<?php

namespace Illuminate\Events;

use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('events', static function ($app) {
            return (new Dispatcher($app))->setQueueResolver(static function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }
}
