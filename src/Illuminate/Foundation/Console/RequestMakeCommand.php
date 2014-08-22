<?php namespace Illuminate\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class RequestMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'request:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new form request class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Request';

	/**
	 * Set the configuration key for the namespace.
	 *
	 * @var string
	 */
	protected $configKey = 'requests';

	/**
	 * Get the controller class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		$name = str_replace('\\', '/', $name);

		return $this->laravel['path.requests'].'/'.$name.'.php';
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/request.stub';
	}

}