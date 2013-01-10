<?php namespace Illuminate\Queue\Jobs;

use Illuminate\Container;

class SyncJob extends Job {

	/**
	 * The IoC container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * The class name of the job.
	 *
	 * @var string
	 */
	protected $job;

	/**
	 * The queue message data.
	 *
	 * @var string
	 */
	protected $data;

	/**
	 * Create a new job instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @param  string  $job
	 * @param  string  $data
	 * @return void
	 */
	public function __construct(Container $container, $job, $data = '')
	{
		$this->job = $job;
		$this->data = $data;
		$this->container = $container;
	}

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->instance = $this->container->make($this->job);

		$this->instance->fire($this, $this->data);
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		//
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		//
	}

}