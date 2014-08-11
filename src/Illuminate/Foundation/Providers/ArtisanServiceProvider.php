<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Foundation\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\TailCommand;
use Illuminate\Foundation\Console\ChangesCommand;
use Illuminate\Foundation\Console\EventCacheCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;

class ArtisanServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * The commands to be registered.
	 *
	 * @var array
	 */
	protected $commands = [
		'Tail',
		'Changes',
		'Environment',
		'EventCache',
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// This Artisan class is a lightweight wrapper for calling into the Artisan
		// command line. If a call to this class is executed we will boot up the
		// entire Artisan command line then pass the method into the main app.
		$this->app->bindShared('artisan', function($app)
		{
			return new Artisan($app);
		});

		foreach ($this->commands as $command)
		{
			$this->{"register{$command}Command"}();
		}

		$this->commands(
			'command.tail', 'command.changes', 'command.environment', 'command.event.cache'
		);
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerTailCommand()
	{
		$this->app->bindShared('command.tail', function($app)
		{
			return new TailCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerChangesCommand()
	{
		$this->app->bindShared('command.changes', function($app)
		{
			return new ChangesCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerEnvironmentCommand()
	{
		$this->app->bindShared('command.environment', function($app)
		{
			return new EnvironmentCommand;
		});
	}

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	protected function registerEventCacheCommand()
	{
		$this->app->bindShared('command.event.cache', function($app)
		{
			return new EventCacheCommand($app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'artisan', 'command.changes', 'command.tail', 'command.environment',
			'command.event.cache',
		];
	}

}
