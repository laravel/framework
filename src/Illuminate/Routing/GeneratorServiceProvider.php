<?php namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Console\MiddlewareMakeCommand;
use Illuminate\Routing\Console\ControllerMakeCommand;

class GeneratorServiceProvider extends ServiceProvider {

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
		$this->registerControllerGenerator();

		$this->registerMiddlewareGenerator();

		$this->commands('command.controller.make', 'command.middleware.make');
	}

	/**
	 * Register the controller generator command.
	 *
	 * @return void
	 */
	protected function registerControllerGenerator()
	{
		$this->app->singleton('command.controller.make', function()
		{
			return new ControllerMakeCommand($this->app['files']);
		});
	}

	/**
	 * Register the middleware generator command.
	 *
	 * @return void
	 */
	protected function registerMiddlewareGenerator()
	{
		$this->app->singleton('command.middleware.make', function()
		{
			return new MiddlewareMakeCommand($this->app['files']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'command.controller.make', 'command.middleware.make',
		);
	}

}
