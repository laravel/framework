<?php namespace Illuminate\Foundation\Console;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Console\Kernel as KernelContract;

class Kernel implements KernelContract {

	/**
	 * The application implementation.
	 *
	 * @var \Illuminate\Contracts\Foundation\Application
	 */
	protected $app;

	/**
	 * The event dispatcher implementation.
	 *
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The bootstrap classes for the application.
	 *
	 * @return void
	 */
	protected $bootstrappers = [
		'Illuminate\Foundation\Bootstrap\DetectEnvironment',
		'Illuminate\Foundation\Bootstrap\LoadConfiguration',
		'Illuminate\Foundation\Bootstrap\RegisterFacades',
		'Illuminate\Foundation\Bootstrap\SetRequestForConsole',
		'Illuminate\Foundation\Bootstrap\RegisterProviders',
		'Illuminate\Foundation\Bootstrap\BootProviders',
	];

	/**
	 * Create a new console kernel instance.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @param  \Illuminate\Contracts\Events\Dispatcher  $events
	 * @return void
	 */
	public function __construct(Application $app, Dispatcher $events)
	{
		$this->app = $app;
		$this->events = $events;
	}

	/**
	 * Run the console application.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return int
	 */
	public function handle($input, $output = null)
	{
		$this->bootstrap();

		$this->app->loadDeferredProviders();

		return (new Artisan($this->app, $this->events))->run($input, $output);
	}

	/**
	 * Bootstrap the application for HTTP requests.
	 *
	 * @return void
	 */
	public function bootstrap()
	{
		if ( ! $this->app->hasBeenBootstrapped())
		{
			$this->app->bootstrapWith($this->bootstrappers);
		}
	}

}
