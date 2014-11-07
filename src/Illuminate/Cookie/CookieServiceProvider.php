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
		$this->app->singleton('cookie', function($app)
		{
			$config = $app['config']['session'];

			return (new CookieJar)->setDefaultPathAndDomain($config['path'], $config['domain']);
		});

		$this->app->alias('cookie', 'Illuminate\Cookie\CookieJar');
		$this->app->alias('cookie', 'Illuminate\Contracts\Cookie\Factory');
		$this->app->alias('cookie', 'Illuminate\Contracts\Cookie\QueueingFactory');
	}

}
