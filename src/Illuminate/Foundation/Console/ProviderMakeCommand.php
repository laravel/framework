<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class ProviderMakeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'provider:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new service provider class';

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new provider creator command instance.
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
			return $this->error('Provider already exists!');
		}

		$this->files->put(
			$path, $this->buildProviderClass($name)
		);

		$this->info('Provider created successfully.');

		$this->call('dump-autoload');
	}

	/**
	 * Build the provider class with the given name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function buildProviderClass($name)
	{
		$stub = $this->files->get(__DIR__.'/stubs/provider.stub');

		return str_replace('{{class}}', $name, $stub);
	}

	/**
	 * Get the provider class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		return $this->laravel['path.providers'].'/'.$name.'.php';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the provider class'),
		);
	}

}