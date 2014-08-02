<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\RouteCacheCommand;
use Illuminate\Foundation\Console\RouteClearCommand;

class RouteCacheServiceProvider extends ServiceProvider {

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
		$this->app->bindShared('command.route.cache', function($app)
		{
			return new RouteCacheCommand($app['files']);
		});

		$this->app->bindShared('command.route.clear', function($app)
		{
			return new RouteClearCommand($app['files']);
		});

		$this->commands('command.route.cache', 'command.route.clear');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('command.route.cache', 'command.route.clear');
	}

}
