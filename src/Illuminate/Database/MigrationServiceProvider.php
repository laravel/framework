<?php

namespace Illuminate\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Console\Migrations\MigrationConflictsCommand;
use Illuminate\Database\Console\Migrations\MigrationDependenciesCommand;
use Illuminate\Database\Console\Migrations\MigrationSuggestOrderCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\MigrationDependencyResolver;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'Migrate' => MigrateCommand::class,
        'MigrateFresh' => FreshCommand::class,
        'MigrateInstall' => InstallCommand::class,
        'MigrateRefresh' => RefreshCommand::class,
        'MigrateReset' => ResetCommand::class,
        'MigrateRollback' => RollbackCommand::class,
        'MigrateStatus' => StatusCommand::class,
        'MigrateMake' => MigrateMakeCommand::class,
        'MigrationDependencies' => MigrationDependenciesCommand::class,
        'MigrationConflicts' => MigrationConflictsCommand::class,
        'MigrationSuggestOrder' => MigrationSuggestOrderCommand::class,
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

        $this->registerDependencyResolver();

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
            $migrations = $app['config']['database.migrations'];

            $table = is_array($migrations) ? ($migrations['table'] ?? null) : $migrations;

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

        $this->app->bind(Migrator::class, fn ($app) => $app['migrator']);
    }

    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files'], $app->basePath('stubs'));
        });
    }

    /**
     * Register the migration dependency resolver.
     *
     * @return void
     */
    protected function registerDependencyResolver()
    {
        $this->app->singleton('migration.dependency.resolver', function ($app) {
            return new MigrationDependencyResolver($app['files'], $app['migrator']);
        });

        $this->app->bind(MigrationDependencyResolver::class, fn ($app) => $app['migration.dependency.resolver']);
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            $this->{"register{$command}Command"}();
        }

        $this->commands(array_values($commands));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton(MigrateCommand::class, function ($app) {
            return new MigrateCommand($app['migrator'], $app[Dispatcher::class]);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateFreshCommand()
    {
        $this->app->singleton(FreshCommand::class, function ($app) {
            return new FreshCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateInstallCommand()
    {
        $this->app->singleton(InstallCommand::class, function ($app) {
            return new InstallCommand($app['migration.repository']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton(MigrateMakeCommand::class, function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $app['migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateRefreshCommand()
    {
        $this->app->singleton(RefreshCommand::class);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateResetCommand()
    {
        $this->app->singleton(ResetCommand::class, function ($app) {
            return new ResetCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateRollbackCommand()
    {
        $this->app->singleton(RollbackCommand::class, function ($app) {
            return new RollbackCommand($app['migrator']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateStatusCommand()
    {
        $this->app->singleton(StatusCommand::class, function ($app) {
            return new StatusCommand($app['migrator']);
        });
    }

    /**
     * Register the migration dependencies command.
     *
     * @return void
     */
    protected function registerMigrationDependenciesCommand()
    {
        $this->app->singleton(MigrationDependenciesCommand::class, function ($app) {
            return new MigrationDependenciesCommand($app['migrator'], $app['migration.dependency.resolver']);
        });
    }

    /**
     * Register the migration conflicts command.
     *
     * @return void
     */
    protected function registerMigrationConflictsCommand()
    {
        $this->app->singleton(MigrationConflictsCommand::class, function ($app) {
            return new MigrationConflictsCommand($app['migrator'], $app['migration.dependency.resolver']);
        });
    }

    /**
     * Register the migration suggest order command.
     *
     * @return void
     */
    protected function registerMigrationSuggestOrderCommand()
    {
        $this->app->singleton(MigrationSuggestOrderCommand::class, function ($app) {
            return new MigrationSuggestOrderCommand($app['migrator'], $app['migration.dependency.resolver']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge([
            'migrator', 'migration.repository', 'migration.creator', 'migration.dependency.resolver',
            Migrator::class, MigrationDependencyResolver::class,
        ], array_values($this->commands));
    }
}
