<?php

namespace Illuminate\Session;

use Illuminate\Support\ServiceProvider;
use Illuminate\Session\Console\SessionTableCommand;

class CommandsServiceProvider extends ServiceProvider
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
        $this->app->singleton('command.session.database', function ($app) {
            return new SessionTableCommand($app['files'], $app['composer']);
        });

        $this->commands('command.session.database');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.session.database'];
    }
}
