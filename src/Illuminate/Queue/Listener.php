<?php namespace Illuminate\Queue;

use Symfony\Component\Process\Process;

class Listener {

	/**
	 * The command working path.
	 *
	 * @var string
	 */
	protected $commandPath;

	/**
	 * Create a new queue listener.
	 *
	 * @param  string  $commandPath
	 * @return void
	 */
	public function __construct($commandPath)
	{
		$this->commandPath = $commandPath;
	}

	/**
	 * Listen to the given queue connection.
	 *
	 * @param  string  $connection
	 * @param  string  $queue
	 * @param  string  $delay
	 * @param  string  $memory
	 * @return void
	 */
	public function listen($connection, $queue, $delay, $memory)
	{
		$process = $this->makeProcess($connection, $queue, $delay, $memory);

		while(true)
		{
			$this->runProcess($process, $memory);
		}
	}

	/**
	 * Run the given process.
	 *
	 * @param  Symfony\Component\Process\Process  $process
	 * @param  int  $memory
	 * @return void
	 */
	public function runProcess(Process $process, $memory)
	{
		$process->run();

		// Once we have run the job we'll go check if the memory limit has been
		// exceeded for the script. If it has, we will kill this script so a
		// process managers will restart this with a clean slate of memory.
		if ($this->memoryExceeded($memory))
		{
			$this->stop(); return;
		}
	}

	/**
	 * Create a new Symfony process for the worker.
	 *
	 * @param  string  $connection
	 * @param  string  $queue
	 * @param  int     $delay
	 * @param  int     $memory
	 * @return Symfony\Component\Process\Process
	 */
	public function makeProcess($connection, $queue, $delay, $memory)
	{
		$string = 'php artisan queue:work %s --queue=%s --delay=%s --memory=%s --sleep';

		$command = sprintf($string, $connection, $queue, $delay, $memory);

		return new Process($command, $this->commandPath);
	}

	/**
	 * Determine if the memory limit has been exceeded.
	 *
	 * @param  int   $memoryLimit
	 * @return bool
	 */
	public function memoryExceeded($memoryLimit)
	{
		return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
	}

	/**
	 * Stop listening and bail out of the script.
	 *
	 * @return void
	 */
	public function stop()
	{
		die;
	}

}