<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;

class EventMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:event';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new event class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Event';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/event.stub';
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace.'\Events';
	}

}
