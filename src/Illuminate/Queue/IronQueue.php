<?php namespace Illuminate\Queue;

use IronMQ;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Queue\Jobs\IronJob;

class IronQueue extends Queue implements QueueInterface {

	/**
	 * The IronMQ instance.
	 *
	 * @var IronMQ
	 */
	protected $iron;

	/**
	 * The current request instance.
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * The name of the default tube.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * Create a new IronMQ queue instance.
	 *
	 * @param  \IronMQ  $iron
	 * @param  \Illuminate\Http\Request  $request
	 * @param  string  $default
	 * @return void
	 */
	public function __construct(IronMQ $iron, Request $request, $default)
	{
		$this->iron = $iron;
		$this->request = $request;
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

		$this->iron->postMessage($this->getQueue($queue), $payload, compact('delay'));
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Queue\Jobs\Job|null
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
	 * Marshal a push queue request and fire the job.
	 *
	 * @return Illuminate\Http\Response
	 */
	public function marshal()
	{
		$this->createPushedIronJob($this->marshalPushedJob())->fire();

		return new Response('OK');
	}

	/**
	 * Marshal out the pushed job and payload.
	 *
	 * @return StdClass
	 */
	protected function marshalPushedJob()
	{
		$r = $this->request;

		return (object) array(
			'id' => $r->header('iron-message-id'), 'body' => $r->getContent(), 'pushed' => true,
		);
	}

	/**
	 * Create a new IronJob for a pushed job.
	 *
	 * @param  \StdClass  $job
	 * @return \Illuminate\Queue\Jobs\IronJob
	 */
	protected function createPushedIronJob($job)
	{
		return new IronJob($this->container, $this->iron, $job, $this->default);
	}

	/**
	 * Get the queue or return the default.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	public function getQueue($queue)
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