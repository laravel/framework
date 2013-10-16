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
			$cookies = new CookieJar($app['request'], $app['encrypter']);

			$config = $app['config']['session'];

			return $cookies->setDefaultPathAndDomain($config['path'], $config['domain']);
		});

		//now add an after filter to send the queued cookies
		$app = $this->app;
		$this->app->after(function($request, $response) use ($app)
		{
			$queuedCookies = $app['cookie']->getQueuedCookies();
			foreach ($queuedCookies as $cookie)
			{
				$response->headers->setCookie($cookie);
			}
		});
	}
}