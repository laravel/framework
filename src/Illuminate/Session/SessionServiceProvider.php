<?php namespace Illuminate\Session;

use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->registerSessionEvents();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['session'] = $this->app->share(function($app)
		{
			// First, we will create the session manager which is responsible for the
			// creation of the various session drivers when they are needed by the
			// application instance, and will resolve them on a lazy load basis.
			$manager = new SessionManager($app);

			$driver = $manager->driver();

			$config = $app['config']['session'];

			// Once we get an instance of the session driver, we need to set a few of
			// the session options based on the application configuration, such as
			// the session lifetime and the sweeper lottery configuration value.
			$driver->setLifetime($config['lifetime']);

			$driver->setSweepLottery($config['lottery']);

			return $driver;
		});
	}

	/**
	 * Register the events needed for session management.
	 *
	 * @return void
	 */
	protected function registerSessionEvents()
	{
		$app = $this->app;

		$config = $app['config']['session'];

		// The session needs to be started and closed, so we will register a before
		// and after events to do all stuff for us. This will manage the loading
		// the session "payloads", as well as writing them after each request.
		if ( ! is_null($config['driver']))
		{
			$app->booting(function() use ($app)
			{
				$app['session']->start($app['cookie']);
			});

			$app->close(function($request, $response) use ($app, $config)
			{
				$app['session']->finish($response, $app['cookie'], $config['lifetime']);
			});
		}
	}

}