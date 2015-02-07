<?php namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Console\SeedCommand;
use Illuminate\Database\Console\SeedMakeCommand;
use Illuminate\Database\Seeder\SeedCreator;

class SeedServiceProvider extends ServiceProvider {

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
		$this->app->singleton('seeder', function()
		{
			return new Seeder;
		});

		$this->registerCommands();
	}

	/**
	 * Register all of the seed commands.
	 *
	 * @return void
	 */
	protected function registerCommands()
	{
		$commands = array('Seed', 'Make');

		// We'll simply spin through the list of commands that are seed related
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
			'command.seed', 'command.seed.make'
		);
	}

	/**
	 * Register the seed console command.
	 *
	 * @return void
	 */
	protected function registerSeedCommand()
	{
		$this->app->singleton('command.seed', function($app)
		{
			return new SeedCommand($app['db']);
		});
	}

	/**
	 * Register the "make" seed command.
	 *
	 * @return void
	 */
	protected function registerMakeCommand()
	{
		$this->registerCreator();

		$this->app->singleton('command.seed.make', function($app)
		{
			// Once we have the seed creator registered, we will create the command
			// and inject the creator. The creator is responsible for the actual file
			// creation of the seeds, and may be extended by these developers.
			$creator = $app['seed.creator'];

			$composer = $app['composer'];

			return new SeedMakeCommand($creator, $composer);
		});
	}

	/**
	 * Register the seed creator.
	 *
	 * @return void
	 */
	protected function registerCreator()
	{
		$this->app->singleton('seed.creator', function($app)
		{
			return new SeedCreator($app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('seeder', 'command.seed');
	}

}
