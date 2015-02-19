<?php namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class EventServiceProvider extends ServiceProvider {
	
	/**
	 * The event handler mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [];

	/**
	 * The subscriber classes to register.
	 *
	 * @var array
	 */
	protected $subscribe = [];

	/**
	 * Register the application's event listeners.
	 *
	 * @param  \Illuminate\Contracts\Events\Dispatcher  $events
	 * @return void
	 */
	public function boot(DispatcherContract $events)
	{
		foreach ($this->listen as $event => $listeners)
		{
			foreach ($listeners as $listener)
			{
				$events->listen($event, $listener);
			}
		}

		foreach ($this->subscribe as $subscriber)
		{
			$events->subscribe($subscriber);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the events and handlers.
	 *
	 * @return array
	 */
	public function listens()
	{
		return $this->listen;
	}

}
