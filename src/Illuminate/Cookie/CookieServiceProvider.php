<?php namespace Illuminate\Cookie;

use Illuminate\Support\ServiceProvider;

class CookieServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('cookie', function($app)
		{
			$config = $app['config']['session'];

			return (new CookieJar)->setDefaultPathAndDomain($config['path'], $config['domain']);
		});
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->app->middleware('Illuminate\Cookie\Guard', [$this->app['encrypter']]);
		$this->app->middleware('Illuminate\Cookie\Queue', [$this->app['cookie']]);
	}

}
