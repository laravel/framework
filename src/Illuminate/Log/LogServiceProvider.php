<?php namespace Illuminate\Log;

use Monolog\Logger;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider {

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
		$this->app->instance(
			'log', new Writer(new Logger($this->app['env']), $this->app['events'])
		);

		$this->app->bind('Psr\Log\LoggerInterface', function()
		{
			return $this->app['log']->getMonolog();
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['log'];
	}

}
