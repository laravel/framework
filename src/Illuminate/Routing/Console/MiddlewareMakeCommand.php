<?php namespace Illuminate\Routing\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MiddlewareMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:middleware';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new middleware class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Middleware';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		if ($this->option('terminable'))
		{
			return __DIR__.'/stubs/middleware-terminable.stub';
		}

		if ($this->option('after'))
		{
			return __DIR__.'/stubs/middleware-after.stub';
		}

		return __DIR__.'/stubs/middleware-before.stub';
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace.'\Http\Middleware';
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('after', null, InputOption::VALUE_NONE, 'Indicates that the after middleware should be generated.'),

			array('terminable', null, InputOption::VALUE_NONE, 'Indicates that the terminable middleware should be generated.'),
		);
	}

}
