<?php namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class MigrationServiceProvider extends ServiceProvider {

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
		$this->registerRepository();

		// Once we have registered the migrator instance we will go ahead and register
		// all of the migration related commands that are used by the "Artisan" CLI
		// so that they may be easily accessed for registering with the consoles.
		$this->registerMigrator();

		$this->registerCommands();
	}

	/**
	 * Register the migration repository service.
	 *
	 * @return void
	 */
	protected function registerRepository()
	{
		$this->app->singleton('migration.repository', function()
		{
			$table = $this->app['config']['database.migrations'];

			return new DatabaseMigrationRepository($this->app['db'], $table);
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
		$this->app->singleton('migrator', function()
		{
			$repository = $this->app['migration.repository'];

			return new Migrator($repository, $this->app['db'], $this->app['files']);
		});
	}

	/**
	 * Register all of the migration commands.
	 *
	 * @return void
	 */
	protected function registerCommands()
	{
		$commands = array('Migrate', 'Rollback', 'Reset', 'Refresh', 'Install', 'Make', 'Status');

		// We'll simply spin through the list of commands that are migration related
		// and register each one of them with an application container. They will
		// be resolved in the Artisan start file and registered on the console.
		foreach ($commands as $command)
		{
			$this->{'register'.$command.'Command'}();
		}

		// Once the commands are registered in the application IoC container we will
		// register them with the Artisan start event so that these are available
		// when the Artisan application actually starts up and is getting used.
		$this->commands(
			'command.migrate', 'command.migrate.make',
			'command.migrate.install', 'command.migrate.rollback',
			'command.migrate.reset', 'command.migrate.refresh',
			'command.migrate.status'
		);
	}

	/**
	 * Register the "migrate" migration command.
	 *
	 * @return void
	 */
	protected function registerMigrateCommand()
	{
		$this->app->singleton('command.migrate', function()
		{
			return new MigrateCommand($this->app['migrator']);
		});
	}

	/**
	 * Register the "rollback" migration command.
	 *
	 * @return void
	 */
	protected function registerRollbackCommand()
	{
		$this->app->singleton('command.migrate.rollback', function()
		{
			return new RollbackCommand($this->app['migrator']);
		});
	}

	/**
	 * Register the "reset" migration command.
	 *
	 * @return void
	 */
	protected function registerResetCommand()
	{
		$this->app->singleton('command.migrate.reset', function()
		{
			return new ResetCommand($this->app['migrator']);
		});
	}

	/**
	 * Register the "refresh" migration command.
	 *
	 * @return void
	 */
	protected function registerRefreshCommand()
	{
		$this->app->singleton('command.migrate.refresh', function()
		{
			return new RefreshCommand;
		});
	}

	protected function registerStatusCommand()
	{
		$this->app->singleton('command.migrate.status', function()
		{
			return new StatusCommand($this->app['migrator']);
		});
	}

	/**
	 * Register the "install" migration command.
	 *
	 * @return void
	 */
	protected function registerInstallCommand()
	{
		$this->app->singleton('command.migrate.install', function()
		{
			return new InstallCommand($this->app['migration.repository']);
		});
	}

	/**
	 * Register the "make" migration command.
	 *
	 * @return void
	 */
	protected function registerMakeCommand()
	{
		$this->registerCreator();

		$this->app->singleton('command.migrate.make', function()
		{
			// Once we have the migration creator registered, we will create the command
			// and inject the creator. The creator is responsible for the actual file
			// creation of the migrations, and may be extended by these developers.
			$creator = $this->app['migration.creator'];

			$composer = $this->app['composer'];

			return new MigrateMakeCommand($creator, $composer);
		});
	}

	/**
	 * Register the migration creator.
	 *
	 * @return void
	 */
	protected function registerCreator()
	{
		$this->app->singleton('migration.creator', function()
		{
			return new MigrationCreator($this->app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'migrator', 'migration.repository', 'command.migrate',
			'command.migrate.rollback', 'command.migrate.reset',
			'command.migrate.refresh', 'command.migrate.install',
			'command.migrate.status', 'migration.creator',
			'command.migrate.make',
		);
	}

}
