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
		$this->app->singleton('cache', function()
		{
			return new CacheManager($this->app);
		});

		$this->app->singleton('cache.store', function()
		{
			return $this->app['cache']->driver();
		});

		$this->app->singleton('memcached.connector', function()
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
		$this->app->singleton('command.cache.clear', function()
		{
			return new Console\ClearCommand($this->app['cache']);
		});

		$this->app->singleton('command.cache.table', function()
		{
			return new Console\CacheTableCommand($this->app['files'], $this->app['composer']);
		});

		$this->commands('command.cache.clear', 'command.cache.table');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'cache', 'cache.store', 'memcached.connector', 'command.cache.clear', 'command.cache.table'
		];
	}

}
