<?php namespace Illuminate\Cookie;

use Illuminate\Support\ServiceProvider;

class CookieServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$app = $this->app;

		$this->app->after(function($request, $response) use ($app)
		{
			foreach ($app['cookie']->getQueuedCookies() as $cookie)
			{
				$response->headers->setCookie($cookie);
			}
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['cookie'] = $this->app->share(function($app)
		{
			$cookies = new CookieJar($app['request'], $app['encrypter']);

			$config = $app['config']['session'];

			return $cookies->setDefaultPathAndDomain($config['path'], $config['domain']);
		});
	}
}