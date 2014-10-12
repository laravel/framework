<?php namespace Illuminate\Foundation;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use SebastianBergmann\Environment\Runtime;

class Composer {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The runtime instance.
	 *
	 * @var \SebastianBergmann\Environment\Runtime
	 */
	protected $runtime;

	/**
	 * The working path to regenerate from.
	 *
	 * @var string
	 */
	protected $workingPath;

	/**
	 * Create a new Composer manager instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  \SebastianBergmann\Environment\Runtime  $runtime
	 * @param  string  $workingPath
	 * @return void
	 */
	public function __construct(Filesystem $files, Runtime $runtime, $workingPath = null)
	{
		$this->files = $files;
		$this->runtime = $runtime;
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
			return $this->runtime->getBinary().' composer.phar';
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
		return (new Process('', $this->workingPath))->setTimeout(null);
	}

	/**
	 * Set the working path used by the class.
	 *
	 * @param  string  $path
	 * @return $this
	 */
	public function setWorkingPath($path)
	{
		$this->workingPath = realpath($path);

		return $this;
	}

}
