<?php namespace Illuminate\Queue;

use Illuminate\Queue\Jobs\Job;

class Worker {

	/**
	 * THe queue manager instance.
	 *
	 * @var \Illuminate\Queue\QueueManager
	 */
	protected $manager;

	/**
	 * Create a new queue worker.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	public function __construct(QueueManager $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Listen to the given queue.
	 *
	 * @param  string  $connection
	 * @param  string  $queue
	 * @param  int     $delay
	 * @param  int     $memory
	 * @param  bool    $sleep
	 * @return void
	 */
	public function pop($connection, $queue = null, $delay = 0, $memory = 128, $sleep = false)
	{
		$connection = $this->manager->connection($connection);

		$job = $connection->pop($queue);

		// If we're able to pull a job off of the stack, we will process it and
		// then make sure we are not exceeding our memory limits for the run
		// which is to protect against run-away memory leakages from here.
		if ( ! is_null($job))
		{
			$this->process($job, $delay);
		}
		elseif ($sleep)
		{
			$this->sleep(1);
		}
	}

	/**
	 * Process a given job from the queue.
	 *
	 * @param  \Illuminate\Queue\Jobs\Job  $job
	 * @param  int  $delay
	 * @return void
	 */
	public function process(Job $job, $delay)
	{
		try
		{
			// First we will fire off the job. Once it is done we will see if it will
			// be auto-deleted after processing and if so we will go ahead and run
			// the delete method on the job. Otherwise we will just keep moving.
			$job->fire();

			if ($job->autoDelete()) $job->delete();
		}

		catch (\Exception $e)
		{
			// If we catch an exception, we will attempt to release the job back onto
			// the queue so it is not lost. This will let is be retried at a later
			// time by another listener (or the same one). We will do that here.
			$job->release($delay);

			throw $e;
		}
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