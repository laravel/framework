<?php namespace Illuminate\Config;

use Illuminate\Filesystem\Filesystem;

class ArrayFileLoader extends AbstractFileLoader {

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
		return $this->getRequire($path);
	}

	/**
	 * Get a file's contents by requiring it.
	 *
	 * @param  string  $path
	 * @return mixed
	 */
	protected function getRequire($path)
	{
		return $this->files->getRequire($path);
	}

}
