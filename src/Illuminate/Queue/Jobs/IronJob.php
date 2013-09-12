<?php namespace Illuminate\Queue\Jobs;

use IronMQ;
use Illuminate\Container\Container;

class IronJob extends Job {

	/**
	 * The IronMQ instance.
	 *
	 * @var IronMQ
	 */
	protected $iron;

	/**
	 * The IronMQ message instance.
	 *
	 * @var array
	 */
	protected $job;

	/**
	 * The name of the queue the job came from.
	 *
	 * @var string
	 */
	protected $queue;

	/**
	 * Indicates if the message was a push message.
	 *
	 * @var bool
	 */
	protected $pushed = false;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @param  IronMQ  $iron
	 * @param  StdClass  $job
	 * @param  string  $queue
	 * @param  bool  $pushed
	 * @return void
	 */
	public function __construct(Container $container,
                                IronMQ $iron,
                                $job,
                                $queue,
                                $pushed = false)
	{
		$this->job = $job;
		$this->iron = $iron;
		$this->queue = $queue;
		$this->pushed = $pushed;
		$this->container = $container;

		$this->job->body = json_decode($this->job->body, true);
	}

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->resolveAndFire($this->job->body);
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		if (isset($this->job->pushed)) return;

		$this->iron->deleteMessage($this->queue, $this->job->id);
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		if ( ! $this->pushed)
		{
			$this->iron->releaseMessage($this->queue, $this->job->id, $delay);
		}
		else
		{
			// Since a pushed job cannot be released back onto the queue,
			// we'll instead create a new job to push onto the queue,
			// and we'll increment the last attempt count by one.
			$payload = $this->job->body;

			$attempt = array_get($payload, 'attempt', 1) + 1;

			$this->container->make('queue')->connection('iron')->later(

				$delay, $payload['job'], $payload['data'], $this->queue, $attempt

			);
		}
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	public function attempts()
	{
		if ($this->pushed)
		{
			return array_get($this->job->body, 'attempt', 1);
		}
		else
		{
			throw new \LogicException("This driver doesn't support attempt counting.");
		}
	}

	/**
	 * Get the job identifier.
	 *
	 * @return string
	 */
	public function getJobId()
	{
		return $this->job->id;
	}

	/**
	 * Get the IoC container instance.
	 *
	 * @return \Illuminate\Container\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Get the underlying IronMQ instance.
	 *
	 * @return IronMQ
	 */
	public function getIron()
	{
		return $this->iron;
	}

	/**
	 * Get the underlying IronMQ job.
	 *
	 * @return array
	 */
	public function getIronJob()
	{
		return $this->job;
	}

}