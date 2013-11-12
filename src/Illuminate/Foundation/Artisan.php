<?php namespace Illuminate\Foundation;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Console\Application as ConsoleApplication;

class Artisan {

	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * The Artisan console instance.
	 *
	 * @var  \Illuminate\Console\Application
	 */
	protected $artisan;

	/**
	 * Create a new Artisan command runner instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Run an Artisan console command by name.
	 *
	 * @param  string  $command
	 * @param  array   $parameters
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return void
	 */
	public function call($command, array $parameters = array(), OutputInterface $output = null)
	{
		$artisan = $this->getArtisan();

		$parameters['command'] = $command;

		// Unless an output interface implementation was specifically passed to us we
		// will use the "NullOutput" implementation by default to keep any writing
		// suppressed so it doesn't leak out to the browser or any other source.
		$output = $output ?: new NullOutput;

		$input = new ArrayInput($parameters);

		return $artisan->find($command)->run($input, $output);
	}

	/**
	 * Get the Artisan console instance.
	 *
	 * @return \Illuminate\Console\Application
	 */
	protected function getArtisan()
	{
		if ( ! is_null($this->artisan)) return $this->artisan;

		$this->app->loadDeferredProviders();

		return $this->artisan = ConsoleApplication::start($this->app);
	}

	/**
	 * Dynamically pass all missing methods to console Artisan.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->app['artisan'], $method), $parameters);
	}

}
