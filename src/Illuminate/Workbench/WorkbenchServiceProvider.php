<?php namespace Illuminate\Workbench;

use Illuminate\Support\ServiceProvider;
use Illuminate\Workbench\Console\WorkbenchMakeCommand;

class WorkbenchServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['package.creator'] = $this->app->share(function($app)
		{
			return new PackageCreator($app['files']);
		});

		$this->app['command.workbench'] = $this->app->share(function($app)
		{
			return new WorkbenchMakeCommand($app['package.creator']);
		});

		$this->commands('command.workbench');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('package.creator', 'command.workbench');
	}

}