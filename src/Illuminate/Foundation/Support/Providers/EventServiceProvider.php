<?php namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class EventServiceProvider extends ServiceProvider {

	/**
	 * Register the application's event listeners.
	 *
	 * @param  \Illuminate\Contracts\Events\Dispatcher  $events
	 * @return void
	 */
	public function boot(DispatcherContract $events)
	{
		if (file_exists($scanned = $this->app['path.storage'].'/framework/events.scanned.php'))
		{
			require $scanned;
		}

		foreach ($this->listen as $event => $listeners)
		{
			foreach ($listeners as $listener)
			{
				$events->listen($event, $listener);
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		//
	}

}
