<?php namespace Illuminate\Queue;

use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Debug\ExceptionHandler;

class Worker {

	/**
	 * The queue manager instance.
	 *
	 * @var \Illuminate\Queue\QueueManager
	 */
	protected $manager;

	/**
	 * The failed job provider implementation.
	 *
	 * @var \Illuminate\Queue\Failed\FailedJobProviderInterface
	 */
	protected $failer;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The cache repository implementation.
	 *
	 * @var \Illuminate\Contracts\Cache\Repository
	 */
	protected $cache;

	/**
	 * The exception handler instance.
	 *
	 * @var \Illuminate\Foundation\Exceptions\Handler
	 */
	protected $exceptions;

	/**
	 * Create a new queue worker.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @param  \Illuminate\Queue\Failed\FailedJobProviderInterface  $failer
	 * @param  \Illuminate\Contracts\Events\Dispatcher  $events
	 * @return void
	 */
	public function __construct(QueueManager $manager,
                                FailedJobProviderInterface $failer = null,
                                Dispatcher $events = null)
	{
		$this->failer = $failer;
		$this->events = $events;
		$this->manager = $manager;
	}

	/**
	 * Listen to the given queue in a loop.
	 *
	 * @param  string  $connectionName
	 * @param  string  $queue
	 * @param  int     $delay
	 * @param  int     $memory
	 * @param  int     $sleep
	 * @param  int     $maxTries
	 * @return array
	 */
	public function daemon($connectionName, $queue = null, $delay = 0, $memory = 128, $sleep = 3, $maxTries = 0)
	{
		$lastRestart = $this->getTimestampOfLastQueueRestart();

		while (true)
		{
			if ($this->daemonShouldRun())
			{
				$this->runNextJobForDaemon(
					$connectionName, $queue, $delay, $sleep, $maxTries
				);
			}
			else
			{
				$this->sleep($sleep);
			}

			if ($this->memoryExceeded($memory) || $this->queueShouldRestart($lastRestart))
			{
				$this->stop();
			}
		}
	}

	/**
	 * Run the next job for the daemon worker.
	 *
	 * @param  string  $connectionName
	 * @param  string  $queue
	 * @param  int  $delay
	 * @param  int  $sleep
	 * @param  int  $maxTries
	 * @return void
	 */
	protected function runNextJobForDaemon($connectionName, $queue, $delay, $sleep, $maxTries)
	{
		try
		{
			$this->pop($connectionName, $queue, $delay, $sleep, $maxTries);
		}
		catch (Exception $e)
		{
			if ($this->exceptions) $this->exceptions->report($e);
		}
	}

	/**
	 * Determine if the daemon should process on this iteration.
	 *
	 * @return bool
	 */
	protected function daemonShouldRun()
	{
		if ($this->manager->isDownForMaintenance())
		{
			return false;
		}

		return $this->events->until('illuminate.queue.looping') !== false;
	}

	/**
	 * Listen to the given queue.
	 *
	 * @param  string  $connectionName
	 * @param  string  $queue
	 * @param  int     $delay
	 * @param  int     $sleep
	 * @param  int     $maxTries
	 * @return array
	 */
	public function pop($connectionName, $queue = null, $delay = 0, $sleep = 3, $maxTries = 0)
	{
		$connection = $this->manager->connection($connectionName);

		$job = $this->getNextJob($connection, $queue);

		// If we're able to pull a job off of the stack, we will process it and
		// then immediately return back out. If there is no job on the queue
		// we will "sleep" the worker for the specified number of seconds.
		if ( ! is_null($job))
		{
			return $this->process(
				$this->manager->getName($connectionName), $job, $maxTries, $delay
			);
		}

		$this->sleep($sleep);

		return ['job' => null, 'failed' => false];
	}

	/**
	 * Get the next job from the queue connection.
	 *
	 * @param  \Illuminate\Queue\Queue  $connection
	 * @param  string  $queue
	 * @return \Illuminate\Contracts\Queue\Job|null
	 */
	protected function getNextJob($connection, $queue)
	{
		if (is_null($queue)) return $connection->pop();

		foreach (explode(',', $queue) as $queue)
		{
			if ( ! is_null($job = $connection->pop($queue))) return $job;
		}
	}

	/**
	 * Process a given job from the queue.
	 *
	 * @param  string  $connection
	 * @param  \Illuminate\Contracts\Queue\Job  $job
	 * @param  int  $maxTries
	 * @param  int  $delay
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function process($connection, Job $job, $maxTries = 0, $delay = 0)
	{
		if ($maxTries > 0 && $job->attempts() > $maxTries)
		{
			return $this->logFailedJob($connection, $job);
		}

		try
		{
			// First we will fire off the job. Once it is done we will see if it will
			// be auto-deleted after processing and if so we will go ahead and run
			// the delete method on the job. Otherwise we will just keep moving.
			$job->fire();

			return ['job' => $job, 'failed' => false];
		}

		catch (Exception $e)
		{
			// If we catch an exception, we will attempt to release the job back onto
			// the queue so it is not lost. This will let is be retried at a later
			// time by another listener (or the same one). We will do that here.
			if ( ! $job->isDeleted()) $job->release($delay);

			throw $e;
		}
	}

	/**
	 * Log a failed job into storage.
	 *
	 * @param  string  $connection
	 * @param  \Illuminate\Contracts\Queue\Job  $job
	 * @return array
	 */
	protected function logFailedJob($connection, Job $job)
	{
		if ($this->failer)
		{
			$this->failer->log($connection, $job->getQueue(), $job->getRawBody());

			$job->delete();

			$job->failed();

			$this->raiseFailedJobEvent($connection, $job);
		}

		return ['job' => $job, 'failed' => true];
	}

	/**
	 * Raise the failed queue job event.
	 *
	 * @param  string  $connection
	 * @param  \Illuminate\Contracts\Queue\Job  $job
	 * @return void
	 */
	protected function raiseFailedJobEvent($connection, Job $job)
	{
		if ($this->events)
		{
			$data = json_decode($job->getRawBody(), true);

			$this->events->fire('illuminate.queue.failed', array($connection, $job, $data));
		}
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
		$this->events->fire('illuminate.queue.stopping');

		die;
	}

	/**
	 * Sleep the script for a given number of seconds.
	 *
	 * @param  int   $seconds
	 * @return void
	 */
	public function sleep($seconds)
	{
		sleep($seconds);
	}

	/**
	 * Get the last queue restart timestamp, or null.
	 *
	 * @return int|null
	 */
	protected function getTimestampOfLastQueueRestart()
	{
		if ($this->cache)
		{
			return $this->cache->get('illuminate:queue:restart');
		}
	}

	/**
	 * Determine if the queue worker should restart.
	 *
	 * @param  int|null  $lastRestart
	 * @return bool
	 */
	protected function queueShouldRestart($lastRestart)
	{
		return $this->getTimestampOfLastQueueRestart() != $lastRestart;
	}

	/**
	 * Set the exception handler to use in Daemon mode.
	 *
	 * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $handler
	 * @return void
	 */
	public function setDaemonExceptionHandler(ExceptionHandler $handler)
	{
		$this->exceptions = $handler;
	}

	/**
	 * Set the cache repository implementation.
	 *
	 * @param  \Illuminate\Contracts\Cache\Repository  $cache
	 * @return void
	 */
	public function setCache(CacheContract $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * Get the queue manager instance.
	 *
	 * @return \Illuminate\Queue\QueueManager
	 */
	public function getManager()
	{
		return $this->manager;
	}

	/**
	 * Set the queue manager instance.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	public function setManager(QueueManager $manager)
	{
		$this->manager = $manager;
	}

}
