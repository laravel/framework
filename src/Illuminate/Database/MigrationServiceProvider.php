<?php

namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\Migrations\StatusCommand;

class MigrationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'command.migrate',
        'command.migrate.fresh',
        'command.migrate.install',
        'command.migrate.refresh',
        'command.migrate.reset',
        'command.migrate.rollback',
        'command.migrate.status',
        'command.migrate.make',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();

        $this->registerMigrator();

        $this->registerCreator();

        $this->registerCommands($this->commands);
    }

    /**
     * Register the migration repository service.
     *
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['db'], $table);
        });
    }

    /**
     * Register the migrator service.
     *
     * @return void
     */
    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
        $this->app->singleton('migrator', function ($app) {
            $repository = $app['migration.repository'];

            return new Migrator($repository, $app['db'], $app['files'], $app['events']);
        });
    }

    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files']);
        });
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        $factories = $this->getFactories();

        foreach ($commands as $commandName) {
            $this->app->singleton($commandName, $factories[$commandName]);
        }

        $this->commands($commands);
    }

    /**
     * Get class factories.
     *
     * @return array
     */
    protected function getFactories(): array
    {
        return [
            'command.migrate'          => static function ($app) {
                return new MigrateCommand($app['migrator']);
            },
            'command.migrate.fresh'    => static function () {
                return new FreshCommand;
            },
            'command.migrate.install'  => static function ($app) {
                return new InstallCommand($app['migration.repository']);
            },
            'command.migrate.refresh'  => static function () {
                return new RefreshCommand;
            },
            'command.migrate.reset'    => static function ($app) {
                return new ResetCommand($app['migrator']);
            },
            'command.migrate.rollback' => static function ($app) {
                return new RollbackCommand($app['migrator']);
            },
            'command.migrate.status'   => static function ($app) {
                return new StatusCommand($app['migrator']);
            },
            'command.migrate.make'     => static function ($app) {
                // Once we have the migration creator registered, we will create the command
                // and inject the creator. The creator is responsible for the actual file
                // creation of the migrations, and may be extended by these developers.
                $creator = $app['migration.creator'];

                $composer = $app['composer'];

                return new MigrateMakeCommand($creator, $composer);
            },
        ];
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge([
            'migrator', 'migration.repository', 'migration.creator',
        ], $this->commands);
    }
}
