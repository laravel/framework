<?php

namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Console\Seeds\SeederMakeCommand;

class SeedServiceProvider extends ServiceProvider
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
        $this->registerSeedCommand();

        $this->registerMakeCommand();

        $this->app->singleton('seeder', function () {
            return new Seeder;
        });

        $this->commands('command.seed', 'command.seeder.make');
    }

    /**
     * Register the seed console command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['db']);
        });
    }

    /**
     * Register the seeder generator command.
     *
     * @return void
     */
    protected function registerMakeCommand()
    {
        $this->app->singleton('command.seeder.make', function ($app) {
            return new SeederMakeCommand($app['files'], $app['composer']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['seeder', 'command.seed', 'command.seeder.make'];
    }
}
