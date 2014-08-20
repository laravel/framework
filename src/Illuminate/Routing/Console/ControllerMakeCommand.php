<?php namespace Illuminate\Routing\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class ControllerMakeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'controller:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new resource controller class';

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new controller creator command instance.
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
			return $this->error('Controller already exists!');
		}

		$this->files->put(
			$path, $this->buildControllerClass($name)
		);

		$this->info('Controller created successfully.');
	}

	/**
	 * Build the controller class with the given name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function buildControllerClass($name)
	{
		$stub = $this->files->get(__DIR__.'/stubs/controller.stub');

		$stub = str_replace('{{class}}', $name, $stub);

		return str_replace('{{namespace}}', $this->laravel['config']['namespaces.root'], $stub);
	}

	/**
	 * Get the controller class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		return $this->laravel['path.controllers'].'/'.$name.'.php';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the controller class'),
		);
	}

}