<?php namespace Illuminate\Routing;

use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->before();

		if ($this->app->routesAreCached())
		{
			require $this->app->getRouteCachePath();
		}
		else
		{
			$this->map();
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {}

	/**
	 * Called before routes are registered.
	 *
	 * Register any model bindings or pattern based filters.
	 *
	 * @return void
	 */
	public function before() {}

	/**
	 * Define the routes for the application.
	 *
	 * @return void
	 */
	public function map() {}

	/**
	 * Pass dynamic methods onto the router instance.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(
			[$this->app->make('router'), $method], $parameters
		);
	}

}
