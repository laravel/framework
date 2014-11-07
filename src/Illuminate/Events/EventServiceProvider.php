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
		$this->app['events'] = $this->app->share(function($app)
		{
			return new Dispatcher($app);
		});

		$this->app->alias('events', 'Illuminate\Events\Dispatcher');
		$this->app->alias('events', 'Illuminate\Contracts\Events\Dispatcher');
	}

}
