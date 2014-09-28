<?php namespace Illuminate\Routing\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class FilterMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:filter';

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
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		if ($this->option('global'))
		{
			return $this->getGlobalFilterStubPath();
		}
		else
		{
			return $this->getFilterStubPath();
		}
	}

	/**
	 * Get the global filter stub path.
	 *
	 * @return string
	 */
	protected function getGlobalFilterStubPath()
	{
		if ($this->option('after'))
		{
			return __DIR__.'/stubs/filter.global.after.stub';
		}

		return __DIR__.'/stubs/filter.global.stub';
	}

	/**
	 * Get the filter stub path.
	 *
	 * @return string
	 */
	protected function getFilterStubPath()
	{
		if ($this->option('after'))
		{
			return __DIR__.'/stubs/filter.after.stub';
		}

		return __DIR__.'/stubs/filter.stub';
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['after', null, InputOption::VALUE_NONE, 'Indicates the filter will be run after routes are executed.'],

			['global', null, InputOption::VALUE_NONE, 'Indicates the filter should be global.'],
		];
	}

}
