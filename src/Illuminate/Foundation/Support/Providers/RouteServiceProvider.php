<?php namespace Illuminate\Foundation\Support\Providers;

use Closure;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Annotations\Scanner;

class RouteServiceProvider extends ServiceProvider {

	/**
	 * Determines if we will auto-scan in the local environment.
	 *
	 * @var bool
	 */
	protected $scanWhenLocal = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->setRoutingMetaInformation();

		$this->app->call([$this, 'before']);

		if ($this->app->routesAreCached())
		{
			$this->loadCachedRoutes();
		}
		else
		{
			$this->loadRoutes();
		}
	}

	/**
	 * Set some meta information regarding routing.
	 *
	 * @return void
	 */
	protected function setRoutingMetaInformation()
	{
		$this->app['url']->setRootControllerNamespace($this->rootUrlNamespace);

		$this->app['router']->setControllersToScan($this->scan ?: []);
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
		if ($this->app->environment('local') && $this->scanWhenLocal)
		{
			$this->scanRoutes();
		}

		if ($this->app->routesAreScanned())
		{
			$this->loadScannedRoutes();
		}

		$this->app->call([$this, 'map']);
	}

	/**
	 * Scan the routes and write the scanned routes file.
	 *
	 * @return void
	 */
	protected function scanRoutes()
	{
		if (empty($this->scan)) return;

		$scanner = new Scanner($this->scan);

		file_put_contents($this->app->getScannedRoutesPath(), '<?php '.$scanner->getRouteDefinitions());
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
