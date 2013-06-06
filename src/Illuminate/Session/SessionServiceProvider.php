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
		if ($this->getDriver() == 'array') return;

		// The cookie toucher is responsbile for updating the expire time on the cookie
		// so that it is refreshed for each page load. Otherwise it is only set here
		// once by PHP and never updated on each subsequent page load of the apps.
		$this->registerCookieToucher();

		$app = $this->app;

		$this->app->close(function() use ($app)
		{
			$app['session']->save();
		});
	}

	/**
	 * Update the session cookie lifetime on each page load.
	 *
	 * @return void
	 */
	protected function registerCookieToucher()
	{
		$me = $this;

		$this->app->close(function() use ($me)
		{
			if ( ! headers_sent()) $me->touchSessionCookie();
		});
	}

	/**
	 * Update the session identifier cookie with a new expire time.
	 *
	 * @return void
	 */
	public function touchSessionCookie()
	{
		$config = $this->app['config']['session'];

		$expire = $this->getExpireTime($config);

		setcookie($config['cookie'], session_id(), $expire, $config['path'], $config['domain'], false, true);
	}

	/**
	 * Get the new session cookie expire time.
	 *
	 * @param  array  $config
	 * @return int
	 */
	protected function getExpireTime($config)
	{
		return $config['lifetime'] == 0 ? 0 : time() + ($config['lifetime'] * 60);
	}

	/**
	 * Get the session driver name.
	 *
	 * @return string
	 */
	protected function getDriver()
	{
		return $this->app['config']['session.driver'];
	}

}