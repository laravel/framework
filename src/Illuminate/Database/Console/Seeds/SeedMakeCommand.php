<?php namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class SeedMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:seeder';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new seeder class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Seeder';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/seeder.stub';
	}

	/**
	 * Get the destination class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		return $this->laravel->databasePath().'/seeds/'.$name.'.php';
	}

	/**
	 * Parse the name and format according to the root namespace.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function parseName($name)
	{
		return $name;
	}

}
