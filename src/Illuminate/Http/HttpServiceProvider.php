<?php namespace Illuminate\Http;

use Illuminate\Support\ServiceProvider;

class HttpServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['response'] = $this->app->share(function($app)
		{
			return new ResponseFactory($app['view']);
		});
	}

}
