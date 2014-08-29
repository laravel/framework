<?php namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Console\FilterMakeCommand;
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

		$this->registerFilterGenerator();

		$this->commands('command.controller.make', 'command.filter.make');
	}

	/**
	 * Register the controller generator command.
	 *
	 * @return void
	 */
	protected function registerControllerGenerator()
	{
		$this->app->bindShared('command.controller.make', function($app)
		{
			return new ControllerMakeCommand($app['files']);
		});
	}

	/**
	 * Register the filter generator command.
	 *
	 * @return void
	 */
	protected function registerFilterGenerator()
	{
		$this->app->bindShared('command.filter.make', function($app)
		{
			return new FilterMakeCommand($app['files']);
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
			'command.controller.make', 'command.filter.make',
		);
	}

}
