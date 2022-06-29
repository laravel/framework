<?php

namespace Illuminate\Console;

use Illuminate\Console\View\Components;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            Blade::component('line', Components\LineComponent::class);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->loadViewsFrom(
                __DIR__.'/resources/views', 'illuminate.console'
            );
        }
    }
}
