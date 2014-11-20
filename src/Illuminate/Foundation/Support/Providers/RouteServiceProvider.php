<?php namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Annotations\Scanner;

class RouteServiceProvider extends ServiceProvider {

	/**
	 * The controller namespace for the application.
	 *
	 * @var string
	 */
	protected $namespace = '';

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
	protected $scanWhenLocal = false;

	/**
	 * Bootstrap any application services.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function boot(Router $router)
	{
		$this->setRootControllerNamespace();

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
	 * Set the root controller namespace for the application.
	 *
	 * @return void
	 */
	protected function setRootControllerNamespace()
	{
		if (is_null($this->namespace)) return;

		$this->app['Illuminate\Contracts\Routing\UrlGenerator']
						->setRootControllerNamespace($this->namespace);
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
	 * Load the standard routes file for the application.
	 *
	 * @param  string  $path
	 * @return void
	 */
	protected function loadRoutesFrom($path)
	{
		$router = $this->app['Illuminate\Routing\Router'];

		$router->group(['namespace' => $this->namespace], function($router) use ($path)
		{
			require $path;
		});
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
