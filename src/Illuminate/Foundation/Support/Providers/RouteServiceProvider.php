<?php namespace Illuminate\Foundation\Support\Providers;

use Closure;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->app->call([$this, 'before']);

		if ($this->app->routesAreCached())
			return $this->loadCachedRoutes();

		$this->loadRoutes();
	}

	/**
	 * Load the cached routes for the application.
	 *
	 * @return void
	 */
	protected function loadCachedRoutes()
	{
		$this->app->booted(function()
		{
			require $this->app->getCachedRoutesPath();
		});
	}

	/**
	 * Load the application routes.
	 *
	 * @return void
	 */
	protected function loadRoutes()
	{
		if ($this->app->routesAreScanned())
			$this->loadScannedRoutes();

		$this->app->call([$this, 'map']);
	}

	/**
	 * Load the scanned application routes.
	 *
	 * @return void
	 */
	protected function loadScannedRoutes()
	{
		$this->app->booted(function()
		{
			$router = app('Illuminate\Contracts\Routing\Registrar');

			require $this->app->getScannedRoutesPath();
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {}

	/**
	 * Register the given Closure with the "group" function namespace set.
	 *
	 * @param  string  $namespace
	 * @param  \Closure  $callback
	 * @return void
	 */
	protected function namespaced($namespace, Closure $callback)
	{
		if (empty($namespace))
		{
			$callback($this->app['router']);
		}
		else
		{
			$this->app['router']->group(compact('namespace'), $callback);
		}
	}

	/**
	 * Pass dynamic methods onto the router instance.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array([$this->app['router'], $method], $parameters);
	}

}
