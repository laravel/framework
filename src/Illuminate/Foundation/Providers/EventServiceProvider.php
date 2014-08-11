<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;

abstract class EventServiceProvider extends ServiceProvider {

	/**
	 * Get the directories to scan for events.
	 *
	 * @return array
	 */
	public function scan()
	{
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function boot()
	{
		$this->app['config']->set('app.events.scan', $this->scan());

		if ($this->app->eventsAreCached()))
		{
			require $this->app->getEventCachePath();
		}
	}

}