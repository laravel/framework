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
	protected $name = 'make:provider';

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
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/provider.stub';
	}

}
