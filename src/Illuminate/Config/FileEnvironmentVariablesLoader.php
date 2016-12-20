<?php namespace Illuminate\Config;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FileNotFoundException;

class FileEnvironmentVariablesLoader implements EnvironmentVariablesLoaderInterface {

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
	 * Create a new file environment loader instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $path
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

		try
		{
			return array_dot($this->files->getRequire($this->getFile($environment)));
		}
		catch (FileNotFoundException $e)
		{
			return [];
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
			return $this->path.'/.env.'.$environment.'.php';
		}

		return $this->path.'/.env.php';
	}

}
