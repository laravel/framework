<?php namespace Illuminate\Cache;

use Illuminate\Support\ServiceProvider;

class CommandsServiceProvider extends ServiceProvider {

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
		$app = $this->app;

		$app['command.cache.clear'] = $app->share(function($app)
		{
			return new Console\ClearCommand($app['cache']);
		});

		$this->commands('command.cache.clear');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('command.cache.clear');
	}

}