<?php

namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Console\Seeds\WipeCommand;

class WipeServiceProvider extends ServiceProvider
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
        $this->app->singleton('wiper', function () {
            return new Seeder;
        });

        $this->registerWipeCommand();

        $this->commands('command.wipe');
    }

    /**
     * Register the wipe console command.
     *
     * @return void
     */
    protected function registerWipeCommand()
    {
        $this->app->singleton('command.wipe', function ($app) {
            return new WipeCommand($app['migrator']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['wiper', 'command.wipe'];
    }
}
