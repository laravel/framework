<?php namespace Illuminate\Events;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('events', function()
		{
			return (new Dispatcher($this->app))->setQueueResolver(function()
			{
				return $this->app['queue.connection'];
			});
		});
	}

}
