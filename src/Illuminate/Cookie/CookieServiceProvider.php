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
		$this->app->singleton('cookie', function()
		{
			$config = $this->app['config']['session'];

			return (new CookieJar)->setDefaultPathAndDomain($config['path'], $config['domain']);
		});
	}

}
