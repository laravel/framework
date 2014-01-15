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
		$this->app->bindShared('cache', function($app)
		{
			return new CacheManager($app);
		});

		$this->app->bindShared('cache.store', function($app)
		{
			// First, we will create the cache manager which is responsible for the
			// creation of the various cache drivers when they are needed by the
			// application instance, and will resolve them on a lazy load basis.
			$manager = $app['cache'];

			return $manager->driver();
		});

		$this->app->bindShared('memcached.connector', function()
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
		$this->app->bindShared('command.cache.clear', function($app)
		{
			return new Console\ClearCommand($app['cache'], $app['files']);
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
		return array('cache', 'cache.store', 'memcached.connector', 'command.cache.clear');
	}

}
