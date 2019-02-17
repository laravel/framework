<?php

namespace Illuminate\Log;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\RegistrableProvider;

class LogServiceProvider extends ServiceProvider implements RegistrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('log', function () {
            return new LogManager($this->app);
        });
    }
}
