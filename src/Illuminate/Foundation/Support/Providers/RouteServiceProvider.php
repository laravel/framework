<?php namespace Illuminate\Foundation\Support\Providers;

use Closure;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Annotations\Scanner;

class RouteServiceProvider extends ServiceProvider {

	/**
	 * The root namespace to assume when generating URLs to actions.
	 *
	 * @var string
	 */
	protected $rootUrlNamespace = null;

	/**
	 * The controllers to scan for route annotations.
	 *
	 * @var array
	 */
	protected $scan = [];

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
		$this->app['url']->setRootControllerNamespace($this->rootUrlNamespace);

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

		if ( ! empty($this->scan) && $this->app->routesAreScanned())
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
	 * Get the classes to be scanned by the provider.
	 *
	 * @return array
	 */
	public function scans()
	{
		return $this->scan;
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
