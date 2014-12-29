<?php namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;

class SyncQueue extends Queue implements QueueContract {

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return mixed
	 */
	public function push($job, $data = '', $queue = null)
	{
		$this->resolveJob($this->createPayload($job, $data, $queue))->fire();

		return 0;
	}

	/**
	 * Push a raw payload onto the queue.
	 *
	 * @param  string  $payload
	 * @param  string  $queue
	 * @param  array   $options
	 * @return mixed
	 */
	public function pushRaw($payload, $queue = null, array $options = array())
	{
		//
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return mixed
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		return $this->push($job, $data, $queue);
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Contracts\Queue\Job|null
	 */
	public function pop($queue = null)
	{
		//
	}

	/**
	 * Resolve a Sync job instance.
	 *
	 * @param  string  $payload
	 * @return \Illuminate\Queue\Jobs\SyncJob
	 */
	protected function resolveJob($payload)
	{
		return new Jobs\SyncJob($this->container, $payload);
	}

}
