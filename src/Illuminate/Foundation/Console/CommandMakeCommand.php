<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CommandMakeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Create a new Artisan command";

	/**
	 * Create a new command creator command.
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
		$path = $this->getPath();

		$stub = $this->files->get(__DIR__.'/stubs/command.stub');

		// We'll grab the class name to determine the file name. Since applications are
		// typically using the PSR-0 standards we can safely assume the classes name
		// will correspond to what the actual file should be stored as on storage.
		$file = $path.'/'.$this->input->getArgument('name').'.php';

		$this->files->put($file, $this->formatStub($stub));

		$this->info('Command created successfully.');
	}

	/**
	 * Format the command class stub.
	 *
	 * @param  string  $stub
	 * @return string
	 */
	protected function formatStub($stub)
	{
		$stub = str_replace('{{class}}', $this->input->getArgument('name'), $stub);

		if ( ! is_null($namespace = $this->input->getOption('namespace')))
		{
			$stub = str_replace('{{namespace}}', ' namespace '.$namespace.';', $stub);
		}
		else
		{
			$stub = str_replace('{{namespace}}', '', $stub);
		}

		return $stub;
	}

	/**
	 * Get the path where the command should be stored.
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$path = $this->input->getOption('path');

		if (is_null($path))
		{
			return $this->laravel['path'].'/commands';
		}
		else
		{
			return $this->laravel['path.base'].'/'.$path;
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the command.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('path', null, InputOption::VALUE_OPTIONAL, 'The path where the command should be stored.', null),

			array('namespace', null, InputOption::VALUE_OPTIONAL, 'The command namespace.', null),
		);
	}

}