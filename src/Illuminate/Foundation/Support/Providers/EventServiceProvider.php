<?php namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Events\Annotations\Scanner;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class EventServiceProvider extends ServiceProvider {

	/**
	 * The classes to scan for event annotations.
	 *
	 * @var array
	 */
	protected $scan = [];

	/**
	 * Determines if we will auto-scan in the local environment.
	 *
	 * @var bool
	 */
	protected $scanWhenLocal = false;

	/**
	 * Register the application's event listeners.
	 *
	 * @param  \Illuminate\Contracts\Events\Dispatcher  $events
	 * @return void
	 */
	public function boot(DispatcherContract $events)
	{
		if ($this->app->environment('local') && $this->scanWhenLocal)
		{
			$this->scanEvents();
		}

		if ( ! empty($this->scan) && $this->app->eventsAreScanned())
		{
			$this->loadScannedEvents();
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
	 * Load the scanned events for the application.
	 *
	 * @return void
	 */
	protected function loadScannedEvents()
	{
		$events = app('Illuminate\Contracts\Events\Dispatcher');

		require $this->app->getScannedEventsPath();
	}

	/**
	 * Scan the events for the application.
	 *
	 * @return void
	 */
	protected function scanEvents()
	{
		if (empty($this->scan)) return;

		$scanner = new Scanner($this->scan);

		file_put_contents(
			$this->app->getScannedEventsPath(), '<?php '.$scanner->getEventDefinitions()
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the classes to be scanned by the provider.
	 *
	 * @return array
	 */
	public function scans()
	{
		return $this->scan;
	}

}
