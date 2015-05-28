<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ModelMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:model';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new Eloquent model class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Model';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if (parent::fire() !== false)
		{
			if ( ! $this->option('no-migration'))
			{
				$table = str_plural(snake_case(class_basename($this->argument('name'))));

				$this->call('make:migration', ['name' => "create_{$table}_table", '--create' => $table]);
			}
		}
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/model.stub';
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace;
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('no-migration', null, InputOption::VALUE_NONE, 'Do not create a new migration file.'),
		);
	}

}
