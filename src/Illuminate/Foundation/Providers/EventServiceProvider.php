<?php namespace Illuminate\Foundation\Providers;

use Illuminate\Support\ServiceProvider;

abstract class EventServiceProvider extends ServiceProvider {

	/**
	 * Set the paths to be scanned for events by "event:cache".
	 *
	 * @param  array  $paths
	 * @return void
	 */
	public function scan(array $paths)
	{
		$this->app['config']->set('app.events.scan', $paths);
	}

}