<?php namespace Illuminate\Queue\Jobs;

use Pheanstalk;
use Pheanstalk_Job;
use Illuminate\Container;

class BeanstalkdJob extends Job {

	/**
	 * The IoC container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * The Pheanstalk instance.
	 *
	 * @var Pheanstalk
	 */
	protected $pheanstalk;

	/**
	 * The Pheanstalk job instance.
	 *
	 * @var Pheanstalk_Job
	 */
	protected $job;

	/**
	 * Create a new job instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @param  Pheanstalk  $pheanstalk
	 * @param  Pheanstalk_Job  $job
	 * @return void
	 */
	public function __construct(Container $container,
                                Pheanstalk $pheanstalk,
                                Pheanstalk_Job $job)
	{
		$this->job = $job;
		$this->container = $container;
		$this->pheanstalk = $pheanstalk;
	}

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	public function fire()
	{
		$payload = unserialize($this->job->getData());

		// Once we have the message payload, we can create the given class and fire
		// it off with the given data. The data is in the messages serialized so
		// we will unserialize it and pass into the jobs in its original form.
		$this->instance = $this->container->make($payload['job']);

		$this->instance->fire($this, $payload['data']);
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->pheanstalk->delete($this->job);
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		$priority = Pheanstalk::DEFAULT_PRIORITY;

		$this->pheanstalk->release($this->job, $priority, $delay);
	}

	/**
	 * Get the IoC container instance.
	 *
	 * @return Illuminate\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Get the underlying Pheanstalk instance.
	 *
	 * @return Pheanstalk
	 */
	public function getPheanstalk()
	{
		return $this->pheanstalk;
	}

	/**
	 * Get the underlying Pheanstalk job.
	 *
	 * @return Pheanstalk_Job
	 */
	public function getPheanstalkJob()
	{
		return $this->job;
	}

}