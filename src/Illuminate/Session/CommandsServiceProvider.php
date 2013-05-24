<?php namespace Illuminate\Session;

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

		$app['command.session.database'] = $app->share(function($app)
		{
			return new Console\MakeTableCommand($app['files']);
		});

		$this->commands('command.session.database');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('command.session.database');
	}

}