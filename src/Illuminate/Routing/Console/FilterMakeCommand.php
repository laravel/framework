<?php namespace Illuminate\Routing\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class FilterMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'filter:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new route filter class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Filter';

	/**
	 * Get the controller class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		return $this->laravel['path.filters'].'/'.$name.'.php';
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/filter.stub';
	}

}