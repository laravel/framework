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
	 * The Artisan application instance.
	 *
	 * @var \Illuminate\Console\Application
	 */
	protected $artisan;

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

		return $this->getArtisan()->run($input, $output);
	}

	/**
	 * Run an Artisan console command by name.
	 *
	 * @param  string  $command
	 * @param  array  $parameters
	 * @return int
	 */
	public function call($command, array $parameters = array())
	{
		$this->bootstrap();

		return $this->getArtisan()->call($command, $parameters);
	}

	/**
	 * Get the output for the last run command.
	 *
	 * @return string
	 */
	public function output()
	{
		$this->bootstrap();

		return $this->getArtisan()->output();
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

		$this->app->loadDeferredProviders();
	}

	/**
	 * Get the Artisan application instance.
	 *
	 * @return \Illuminate\Console\Application
	 */
	protected function getArtisan()
	{
		if (is_null($this->artisan))
		{
			return $this->artisan = new Artisan($this->app, $this->events);
		}

		return $this->artisan;
	}

}
