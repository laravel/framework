<?php namespace Illuminate\Routing;

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
		{
			$this->app->booted(function()
			{
				require $this->app->getRouteCachePath();
			});
		}
		else
		{
			$this->app->call([$this, 'map']);
		}
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
