<?php namespace Illuminate\Routing\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ControllerMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:controller';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new resource controller class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Controller';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		if ($this->option('plain'))
		{
			return __DIR__.'/stubs/controller.plain.stub';
		}

		return __DIR__.'/stubs/controller.stub';
	}

	/**
	 * Build the class with the given name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function buildClass($name)
	{
		$stub = parent::buildClass($name);
		return $this->replaceModelName($stub);
	}

	/**
	 * Build the controller methods with the given model name.
	 *
	 * @param  string  $stub
	 * @return string
	 */
	protected function replaceModelName(&$stub)
	{
		$modelClass = $this->option('model');

		if (is_null($modelClass))
		{
			$model = '$id';
			$modelHint = 'int  $id';
			$modelUse = '';
		}
		else
		{
			$basename = class_basename($modelClass);
			$argument = strtolower($basename);
			$model = $basename . ' ' . '$' . $argument;
			$modelHint = $basename . '  ' . '$' . $argument;
			$modelUse = 'use ' . $modelClass. ";\n";
		}

		$stub = str_replace('{{model}}', $model, $stub);
		$stub = str_replace('{{modelUse}}', $modelUse, $stub);
		$stub = str_replace('{{modelHint}}', $modelHint, $stub);

		return $stub;
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace.'\Http\Controllers';
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('plain', null, InputOption::VALUE_NONE, 'Generate an empty controller class.'),
			array('model', null, InputOption::VALUE_OPTIONAL, 'Optional model class for binding'),
		);
	}

}
