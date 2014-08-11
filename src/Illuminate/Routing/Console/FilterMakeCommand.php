<?php namespace Illuminate\Routing\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class FilterMakeCommand extends Command {

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
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new filter creator command instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$path = $this->getPath($name = $this->argument('name'));

		if ($this->files->exists($path))
		{
			return $this->error('Filter already exists!');
		}

		$this->files->put(
			$path, $this->buildFilterClass($name)
		);

		$this->info('Filter created successfully.');

		$this->call('dump-autoload');
	}

	/**
	 * Build the class with the given name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function buildFilterClass($name)
	{
		$stub = $this->files->get(__DIR__.'/../Generators/stubs/filter.stub');

		return str_replace('{{class}}', $name, $stub);
	}

	/**
	 * Get the class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		return $this->laravel['path.filters'].'/'.$name.'.php';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the filter class'),
		);
	}

}