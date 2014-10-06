<?php namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Stack\Builder as Stack;

class AppServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap any necessary services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->registerMiddlewareShortcuts();

		return $this->app->call([$this, 'stack']);
	}

	/**
	 * Register the middleware short-cuts for routing.
	 *
	 * @return void
	 */
	protected function registerMiddlewareShortcuts()
	{
		$router = $this->app['router'];

		foreach ($this->middleware as $key => $class)
		{
			$router->middleware($key, $class);
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

}
