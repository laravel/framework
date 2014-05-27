<?php namespace Illuminate\Foundation;

use Symfony\Component\Process\Process;
use Illuminate\Filesystem\FilesystemInterface;

class Composer {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\FilesystemInterface
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
	 * @param  \Illuminate\Filesystem\FilesystemInterface  $files
	 * @param  string  $workingPath
	 * @return void
	 */
	public function __construct(FilesystemInterface $files, $workingPath = null)
	{
		$this->files = $files;
		$this->workingPath = $workingPath;
	}

	/**
	 * Regenerate the Composer autoloader files.
	 *
	 * @param  string  $extra
	 * @return void
	 */
	public function dumpAutoloads($extra = '')
	{
		$process = $this->getProcess();

		$process->setCommandLine(trim($this->findComposer().' dump-autoload '.$extra));

		$process->run();
	}

	/**
	 * Regenerate the optimized Composer autoloader files.
	 *
	 * @return void
	 */
	public function dumpOptimized()
	{
		$this->dumpAutoloads('--optimize');
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
			return '"'.PHP_BINARY.'" composer.phar';
		}

		return 'composer';
	}

	/**
	 * Get a new Symfony process instance.
	 *
	 * @return \Symfony\Component\Process\Process
	 */
	protected function getProcess()
	{
		return with(new Process('', $this->workingPath))->setTimeout(null);
	}

	/**
	 * Set the working path used by the class.
	 *
	 * @param  string  $path
	 * @return \Illuminate\Foundation\Composer
	 */
	public function setWorkingPath($path)
	{
		$this->workingPath = realpath($path);

		return $this;
	}

}
