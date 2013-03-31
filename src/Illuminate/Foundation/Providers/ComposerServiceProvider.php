<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Foundation\Composer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\AutoloadCommand;

class ComposerServiceProvider extends ServiceProvider {

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
		$this->app['composer'] = $this->app->share(function($app)
		{
			return new Composer($app['files'], $app['path.base']);
		});

		$this->app['command.dump-autoload'] = $this->app->share(function($app)
		{
			return new AutoloadCommand($app['composer']);
		});

		$this->commands('command.dump-autoload');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('composer', 'command.dump-autoload');
	}

}