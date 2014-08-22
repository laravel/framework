<?php namespace Illuminate\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ConsoleMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'console:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Create a new Artisan command";

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Console command';

	/**
	 * Set the configuration key for the namespace.
	 *
	 * @var string
	 */
	protected $configKey = 'console';

	/**
	 * Replace the class name for the given stub.
	 *
	 * @param  string  $stub
	 * @param  string  $name
	 * @return string
	 */
	protected function replaceClass($stub, $name)
	{
		$stub = parent::replaceClass($stub, $name);

		return str_replace('{{command}}', $this->option('command'), $stub);
	}

	/**
	 * Get the controller class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		$name = str_replace('\\', '/', $name);

		return $this->laravel['path.console'].'/'.$name.'.php';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the command.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that should be assigned.', 'command:name'),
		);
	}

}
