<?php namespace Illuminate\Str;

use Illuminate\Support\ServiceProvider;

class StrServiceProvider extends ServiceProvider {

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
		$this->registerStr();
	}

	/**
	 * Register the Str instance.
	 *
	 * @return void
	 */
	protected function registerStr()
	{
		$this->app['str'] = $this->app->share(function($app)
		{
			$inflector = new Inflector;
			$inflector->setLanguage($app['config']['app.locale']);
			$inflector->setAsciiMap($app['config']['str.ascii']);
			$inflector->setRemoveList($app['config']['str.remove']);
			return $inflector;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('str');
	}

}
