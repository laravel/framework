<?php namespace Illuminate\Config;

use Illuminate\Filesystem\Filesystem;

abstract class AbstractFileEnvironmentVariablesLoader implements EnvironmentVariablesLoaderInterface {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The path to the configuration files.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The file extension used.
	 *
	 * @var string
	 */
	protected $fileExtension;

	/**
	 * Create a new file environment loader instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files, $path = null)
	{
		$this->files = $files;
		$this->path = $path ?: base_path();
	}

	/**
	 * Load the environment variables for the given environment.
	 *
	 * @param  string  $environment
	 * @return array
	 */
	public function load($environment = null)
	{
		if ($environment == 'production') $environment = null;

		if ( ! $this->files->exists($path = $this->getFile($environment)))
		{
			return array();
		}
		else
		{
			return array_dot($this->loadFile($path));
		}
	}

	/**
	 * Get the file for the given environment.
	 *
	 * @param  string  $environment
	 * @return string
	 */
	protected function getFile($environment)
	{
		if ($environment)
		{
			return $this->path.'/.env.'.$environment.'.'.$this->fileExtension;
		}
		else
		{
			return $this->path.'/.env.'.$this->fileExtension;
		}
	}

	/**
	 * Load a file's contents.
	 *
	 * @param  string  $path
	 * @return mixed
	 */
	abstract protected function loadFile($path);

}
