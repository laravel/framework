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
		$this->setupDefaultDriver();

		$this->registerSessionManager();

		$this->registerSessionDriver();
	}

	/**
	 * Setup the default session driver for the application.
	 *
	 * @return void
	 */
	protected function setupDefaultDriver()
	{
		if ($this->app->runningInConsole())
		{
			$this->app['config']['session.driver'] = 'array';
		}
	}

	/**
	 * Register the session manager instance.
	 *
	 * @return void
	 */
	protected function registerSessionManager()
	{
		$this->app['session.manager'] = $this->app->share(function($app)
		{
			return new SessionManager($app);
		});
	}

	/**
	 * Register the session driver instance.
	 *
	 * @return void
	 */
	protected function registerSessionDriver()
	{
		$this->app['session'] = $this->app->share(function($app)
		{
			// First, we will create the session manager which is responsible for the
			// creation of the various session drivers when they are needed by the
			// application instance, and will resolve them on a lazy load basis.
			$manager = $app['session.manager'];

			return $manager->driver();
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
			$this->registerBootingEvent();

			$this->registerCloseEvent();
		}
	}

	/**
	 * Register the session booting event.
	 *
	 * @return void
	 */
	protected function registerBootingEvent()
	{
		$app = $this->app;

		$this->app->booting(function($app) use ($app)
		{
			$app['session']->start();
		});
	}

	/**
	 * Register the session close event.
	 *
	 * @return void
	 */
	protected function registerCloseEvent()
	{
		$app = $this->app;

		$this->app->close(function() use ($app)
		{
			$app['session']->save();
		});
	}

}