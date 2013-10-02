<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Foundation\Artisan;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\ChangesCommand;
use Illuminate\Foundation\Console\EnvironmentCommand;

class ArtisanServiceProvider extends ServiceProvider {

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
		$this->app['artisan'] = $this->app->share(function($app)
		{
			return new Artisan($app);
		});

		$this->app['command.changes'] = $this->app->share(function($app)
		{
			return new ChangesCommand;
		});

		$this->app['command.environment'] = $this->app->share(function($app)
		{
			return new EnvironmentCommand;
		});

		$this->commands('command.changes', 'command.environment');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('artisan', 'command.changes', 'command.environment');
	}

}