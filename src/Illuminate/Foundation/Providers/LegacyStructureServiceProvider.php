<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;

class LegacyStructureServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->app->booted(function()
		{
			$env = $this->app->environment();

			// The start scripts gives this application the opportunity to override
			// any of the existing IoC bindings, as well as register its own new
			// bindings for things like repositories, etc. We'll load it here.
			$path = $this->app['path'].'/start/global.php';

			if (file_exists($path))
			{
				require $path;
			}

			// The environment start script is only loaded if it exists for the app
			// environment currently active, which allows some actions to happen
			// in one environment while not in the other, keeping things clean.
			$path = $this->app['path']."/start/{$env}.php";

			if (file_exists($path))
			{
				require $path;
			}

			// The Application routes are kept separate from the application starting
			// just to keep the file a little cleaner. We'll go ahead and load in
			// all of the routes now and return the application to the callers.
			$routes = $this->app['path'].'/routes.php';

			if (file_exists($routes))
			{
				require $routes;
			}
		});

	}

}
