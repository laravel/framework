<?php namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Events\Annotations\Scanner;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class EventServiceProvider extends ServiceProvider {

	use AppNamespaceDetectorTrait;

	/**
	 * Determines if we will auto-scan in the local environment.
	 *
	 * @var bool
	 */
	protected $scanWhenLocal = true;

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

		if ($this->app->eventsAreScanned())
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
		$scanner = new Scanner(app_path(), $this->getAppNamespace());

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

}
