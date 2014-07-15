<?php namespace Illuminate\Config;

use Illuminate\Filesystem\Filesystem;

class ArrayFileEnvironmentVariablesLoader extends AbstractFileEnvironmentVariablesLoader {

	/**
	 * The file extension used.
	 *
	 * @var string
	 */
	protected $fileExtension = 'php';

	/**
	 * Load a file's contents.
	 *
	 * @param  string  $path
	 * @return mixed
	 */
	protected function loadFile($path)
	{
		return $this->files->getRequire($path);
	}

}
