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
		$this->app['cookie'] = $this->app->share(function($app)
		{
			return new CookieJar($app['request'], $app['encrypter']);
		});
	}

}