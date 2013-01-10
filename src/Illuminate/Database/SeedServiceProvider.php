<?php namespace Illuminate\Database;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Console\SeedCommand;

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
		$this->registerSeedCommand();

		$this->app['seeder'] = $this->app->share(function($app)
		{
			return new Seeder($app['files'], $app['events']);
		});

		$this->commands('command.seed');
	}

	/**
	 * Register the seed console command.
	 *
	 * @return void
	 */
	protected function registerSeedCommand()
	{
		$this->app['command.seed'] = $this->app->share(function($app)
		{
			$path = $app['path'].'/database/seeds';

			return new SeedCommand($app['db'], $app['seeder'], $app['events'], $path);
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