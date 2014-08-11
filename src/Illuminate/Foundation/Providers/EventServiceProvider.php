<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;

abstract class EventServiceProvider extends ServiceProvider {

	/**
	 * Create a new service provider instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function __construct($app)
	{
		parent::__construct($app);

		$app->booted(function() { $this->loadCachedEvents(); });
	}

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
	 * Load the cached events.
	 *
	 * @return void
	 */
	protected function loadCachedEvents()
	{
		$this->app['config']->set('app.events.scan', $this->scan());

		if ($this->app->eventsAreCached())
		{
			require $this->app->getEventCachePath();
		}
	}

	/**
	 * Get the directories to scan for events.
	 *
	 * @return array
	 */
	public function scan()
	{
		return [];
	}

}