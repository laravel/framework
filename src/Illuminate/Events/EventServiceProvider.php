<?php

namespace Illuminate\Events;

use Illuminate\Contracts\Foundation\Application;
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
        $this->app->singleton('events', fn (Application $app): Dispatcher => new Dispatcher($app));
    }
}
