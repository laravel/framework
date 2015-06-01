<?php namespace Illuminate\Events;

use Illuminate\Contracts\Queue\Factory;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('events', function($app)
		{
			return (new Dispatcher($app))->setQueueResolver(function() use ($app)
			{
				return $app->make(Factory::class);
			});
		});
	}

}
