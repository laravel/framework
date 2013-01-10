<?php namespace Illuminate\Queue;

use IronMQ;
use Illuminate\Queue\Jobs\IronJob;

class IronQueue extends Queue implements QueueInterface {

	/**
	 * The IronMQ instance.
	 *
	 * @var IronMQ
	 */
	protected $iron;

	/**
	 * The name of the default tube.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * Create a new IronMQ queue instance.
	 *
	 * @param  IronMQ  $iron
	 * @param  string  $default
	 * @return void
	 */
	public function __construct(IronMQ $iron, $default)
	{
		$this->iron = $iron;
		$this->default = $default;
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

		$this->iron->postMessage($this->getQueue($queue), $payload);
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

		$payload = array('body' => $payload, 'delay' => $delay);

		$this->iron->postMessage($this->getQueue($queue), $payload);
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return Illuminate\Queue\Jobs\Job|null
	 */
	public function pop($queue = null)
	{
		$queue = $this->getQueue($queue);

		$job = $this->iron->getMessage($queue);

		if ( ! is_null($job))
		{
			return new IronJob($this->container, $this->iron, $job, $queue);
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
	 * Get the underlying IronMQ instance.
	 *
	 * @return IronMQ
	 */
	public function getIron()
	{
		return $this->iron;
	}

}