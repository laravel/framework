<?php namespace Illuminate\Queue;

use Pheanstalk_Job;
use Pheanstalk_Pheanstalk as Pheanstalk;
use Illuminate\Queue\Jobs\BeanstalkdJob;

class BeanstalkdQueue extends Queue implements QueueInterface {

	/**
	 * The Pheanstalk instance.
	 *
	 * @var Pheanstalk
	 */
	protected $pheanstalk;

	/**
	 * The name of the default tube.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * Create a new Beanstalkd queue instance.
	 *
	 * @param  Pheanstalk  $pheanstalk
	 * @param  string  $default
	 * @return void
	 */
	public function __construct(Pheanstalk $pheanstalk, $default)
	{
		$this->default = $default;
		$this->pheanstalk = $pheanstalk;
	}

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return void
	 */
	public function push($job, $data = '', $queue = null)
	{
		$payload = $this->createPayload($job, $data);

		$this->pheanstalk->useTube($this->getQueue($queue))->put($payload);
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  int     $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return void
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		$payload = $this->createPayload($job, $data);

		$pheanstalk = $this->pheanstalk->useTube($this->getQueue($queue));

		$pheanstalk->put($payload, Pheanstalk::DEFAULT_PRIORITY, $delay);
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Queue\Jobs\Job|null
	 */
	public function pop($queue = null)
	{
		$job = $this->pheanstalk->watchOnly($this->getQueue($queue))->reserve(0);

		if ($job instanceof Pheanstalk_Job)
		{
			return new BeanstalkdJob($this->container, $this->pheanstalk, $job);
		}
	}

	/**
	 * Get the queue or return the default.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	protected function getQueue($queue)
	{
		return $queue ?: $this->default;
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

}