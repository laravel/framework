<?php namespace Illuminate\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

abstract class GeneratorCommand extends Command {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Set the configuration key for the namespace.
	 *
	 * @var string
	 */
	protected $configKey = '';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type;

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
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	abstract protected function getStub();

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if ($this->files->exists($path = $this->getPath($name = $this->getNameInput())))
		{
			return $this->error($this->type.' already exists!');
		}

		$this->makeDirectory($path);

		$this->files->put($path, $this->buildClass($name));

		$this->info($this->type.' created successfully.');
	}

	/**
	 * Get the destination class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		$name = str_replace('\\', '/', $name);

		return $this->laravel['path.'.$this->configKey].'/'.$name.'.php';
	}

	/**
	 * Build the directory for the class if necessary.
	 *
	 * @param  string  $path
	 * @return string
	 */
	protected function makeDirectory($path)
	{
		if ( ! $this->files->isDirectory(dirname($path)))
		{
			$this->files->makeDirectory(dirname($path), 0777, true, true);
		}
	}

	/**
	 * Build the controller class with the given name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function buildClass($name)
	{
		$stub = $this->files->get($this->getStub());

		return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
	}

	/**
	 * Replace the namespace for the given stub.
	 *
	 * @param  string  $stub
	 * @param  string  $name
	 * @return $this
	 */
	protected function replaceNamespace(&$stub, $name)
	{
		$stub = str_replace(
			'{{namespace}}', $this->getNamespaceWithSuffix($this->configKey, $name), $stub
		);

		return $this;
	}

	/**
	 * Get the fully qualified class name.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getFullClassName($name)
	{
		return trim($this->laravel['config']['namespaces.'.$this->configKey], '\\').'\\'.$name;
	}

	/**
	 * Get the full namespace name by type and suffix.
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @return string
	 */
	protected function getNamespaceWithSuffix($type, $name)
	{
		$suffix = $this->getNamespaceSuffix($name);

		return trim($this->laravel['config']['namespaces.'.$type].$suffix, '\\');
	}

	/**
	 * Get the namespace suffix to be added to the root namespace.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getNamespaceSuffix($name)
	{
		return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
	}

	/**
	 * Replace the class name for the given stub.
	 *
	 * @param  string  $stub
	 * @param  string  $name
	 * @return string
	 */
	protected function replaceClass($stub, $name)
	{
		$name = str_replace($this->getNamespaceSuffix($name).'\\', '', $name);

		return str_replace('{{class}}', $name, $stub);
	}

	/**
	 * Get the desired class name from the input.
	 *
	 * @return string
	 */
	protected function getNameInput()
	{
		return $this->argument('name');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the class'),
		);
	}

}
