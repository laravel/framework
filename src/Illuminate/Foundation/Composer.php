<?php namespace Illuminate\Foundation;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class Composer {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The working path to regenerate from.
	 *
	 * @var string
	 */
	protected $workingPath;

	/**
	 * Create a new Composer manager instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @param  string  $workingPath
	 * @return void
	 */
	public function __construct(Filesystem $files, $workingPath)
	{
		$this->files = $files;
		$this->workingPath = $workingPath;
	}

	/**
	 * Regenerate the Composer autoloader files.
	 *
	 * @return void
	 */
	public function dumpAutoloads($path = null)
	{
		$process = $this->getProcess($path);

		$process->setCommandLine($this->findComposer().' dump-autoload --optimize');

		$process->run();
	}

	/**
	 * Get the composer command for the environment.
	 *
	 * @return string
	 */
	protected function findComposer()
	{
		if ($this->files->exists($this->workingPath.'/composer.phar'))
		{
			return "php '{$this->workingPath}/composer.phar'";
		}
		else
		{
			return 'composer';
		}
	}

	/**
	 * Get a new Symfony process instance.
	 *
	 * @return Symfony\Component\Process\Process
	 */
	protected function getProcess($path = null)
	{
		if (!$path) $path = $this->workingPath;

		return new Process('', $path);
	}

}