<?php namespace Illuminate\Cache;

use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider {

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
		$this->app['cache'] = $this->app->share(function($app)
		{
			return new CacheManager($app);
		});

		$this->app['memcached.connector'] = $this->app->share(function()
		{
			return new MemcachedConnector;
		});

		$this->registerCommands();
	}

	/**
	 * Register the cache related console commands.
	 *
	 * @return void
	 */
	public function registerCommands()
	{
		$this->app['command.cache.clear'] = $this->app->share(function($app)
		{
			$manifestFile = $app['config']['app.manifest'].'/services.json';

			// The cache clear command clears the entire cache as well as the
			// service providers manifest file, which contains a list of all
			// service providers mapped to the services they provide.
			return new Console\ClearCommand(

				$app['cache'], $app['files'], $manifestFile

			);
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
		return array('cache', 'memcached.connector', 'command.clear.cache');
	}

}