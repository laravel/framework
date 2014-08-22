<?php namespace Illuminate\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class ProviderMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'provider:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new service provider class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Provider';

	/**
	 * Set the configuration key for the namespace.
	 *
	 * @var string
	 */
	protected $configKey = 'providers';

	/**
	 * Get the controller class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		return $this->laravel['path.providers'].'/'.$name.'.php';
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/provider.stub';
	}

}