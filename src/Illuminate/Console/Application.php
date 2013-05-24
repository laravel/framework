<?php namespace Illuminate\Console;

use Illuminate\Container\Container;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Application extends \Symfony\Component\Console\Application {

	/**
	 * The exception handler instance.
	 *
	 * @var \Illuminate\Exception\Handler
	 */
	protected $exceptionHandler;

	/**
	 * The Laravel application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $laravel;

	/**
	 * Start a new Console application.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return \Illuminate\Console\Application
	 */
	public static function start($app)
	{
		$artisan = require __DIR__.'/start.php';

		$artisan->setAutoExit(false);

		// If the event dispatcher is set on the application, we will fire an event
		// with the Artisan instance to provide each listener the opportunity to
		// register their commands on this application before it gets started.
		if (isset($app['events']))
		{
			$app['events']->fire('artisan.start', array($artisan));
		}

		return $artisan;
	}

	/**
	 * Add a command to the console.
	 *
	 * @param  \Symfony\Component\Console\Command\Command  $command
	 * @return \Symfony\Component\Console\Command\Command
	 */
	public function add(SymfonyCommand $command)
	{
		if ($command instanceof Command)
		{
			$command->setLaravel($this->laravel);
		}

		return $this->addToParent($command);
	}

	/**
	 * Add the command to the parent instance.
	 *
	 * @param  \Symfony\Component\Console\Command  $command
	 * @return \Symfony\Component\Console\Command
	 */
	protected function addToParent($command)
	{
		return parent::add($command);
	}

	/**
	 * Add a command, resolving through the application.
	 *
	 * @param  string  $command
	 * @return void
	 */
	public function resolve($command)
	{
		return $this->add($this->laravel[$command]);
	}

	/**
	 * Resolve an array of commands through the application.
	 *
	 * @param  array|dynamic  $commands
	 * @return void
	 */
	public function resolveCommands($commands)
	{
		$commands = is_array($commands) ? $commands : func_get_args();

		foreach ($commands as $command)
		{
			$this->resolve($command);
		}
	}

	/**
	 * Get the default input definitions for the applications.
	 *
	 * @return \Symfony\Component\Console\Input\InputDefinition
	 */
	protected function getDefaultInputDefinition()
	{
		$definition = parent::getDefaultInputDefinition();

		$definition->addOption($this->getEnvironmentOption());

		return $definition;
	}

	/**
	 * Get the global environment option for the definition.
	 *
	 * @return \Symfony\Component\Console\Input\InputOption
	 */
	protected function getEnvironmentOption()
	{
		$message = 'The environment the command should run under.';

		return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message);
	}

	/**
	 * Render the given exception.
	 *
	 * @param  Exception  $e
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return void
	 */
	public function renderException($e, $output)
	{
		// If we have an exception handler instance, we will call that first in case
		// it has some handlers that need to be run first. We will pass "true" as
		// the second parameter to indicate that it's handling a console error.
		if (isset($this->exceptionHandler))
		{
			$this->exceptionHandler->handleConsole($e);
		}

		return parent::renderException($e, $output);
	}

	/**
	 * Set the exception handler instance.
	 *
	 * @param  \Illuminate\Exception\Handler  $handler
	 * @return void
	 */
	public function setExceptionHandler($handler)
	{
		$this->exceptionHandler = $handler;
	}

	/**
	 * Set the Laravel application instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $laravel
	 * @return void
	 */
	public function setLaravel($laravel)
	{
		$this->laravel = $laravel;
	}

}