<?php

namespace Illuminate\Log;

use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('log', function () {
            $configurator = $this->app->getLogConfigurator();

            return (new $configurator($this->app))->configure();
        });
    }
}
