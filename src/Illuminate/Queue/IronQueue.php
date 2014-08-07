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
	 * Indicates if the messages should be encrypted.
	 *
	 * @var bool
	 */
	protected $shouldEncrypt;

	/**
	 * Create a new IronMQ queue instance.
	 *
	 * @param  \IronMQ  $iron
	 * @param  \Illuminate\Http\Request  $request
	 * @param  string  $default
	 * @param  bool  $shouldEncrypt
	 * @return void
	 */
	public function __construct(IronMQ $iron, Request $request, $default, $shouldEncrypt = false)
	{
		$this->iron = $iron;
		$this->request = $request;
		$this->default = $default;
		$this->shouldEncrypt = $shouldEncrypt;
	}

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
		return $this->pushRaw($this->createPayload($job, $data, $queue), $queue);
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
		if ($this->shouldEncrypt) $payload = $this->crypt->encrypt($payload);

		return $this->iron->postMessage($this->getQueue($queue), $payload, $options)->id;
	}

	/**
	 * Push a raw payload onto the queue after encrypting the payload.
	 *
	 * @param  string  $payload
	 * @param  string  $queue
	 * @param  int     $delay
	 * @return mixed
	 */
	public function recreate($payload, $queue = null, $delay)
	{
		$options = array('delay' => $this->getSeconds($delay));

		return $this->pushRaw($payload, $queue, $options);
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
		$delay = $this->getSeconds($delay);

		$payload = $this->createPayload($job, $data, $queue);

		return $this->pushRaw($payload, $queue, compact('delay'));
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

		// If we were able to pop a message off of the queue, we will need to decrypt
		// the message body, as all Iron.io messages are encrypted, since the push
		// queues will be a security hazard to unsuspecting developers using it.
		if ( ! is_null($job))
		{
			$job->body = $this->parseJobBody($job->body);

			return new IronJob($this->container, $this, $job);
		}
	}

	/**
	 * Delete a message from the Iron queue.
	 *
	 * @param  string  $queue
	 * @param  string  $id
	 * @return void
	 */
	public function deleteMessage($queue, $id)
	{
		$this->iron->deleteMessage($queue, $id);
	}

	/**
	 * Marshal a push queue request and fire the job.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function marshal()
	{
		$this->createPushedIronJob($this->marshalPushedJob())->fire();

		return new Response('OK');
	}

	/**
	 * Marshal out the pushed job and payload.
	 *
	 * @return object
	 */
	protected function marshalPushedJob()
	{
		$r = $this->request;

		$body = $this->parseJobBody($r->getContent());

		return (object) array(
			'id' => $r->header('iron-message-id'), 'body' => $body, 'pushed' => true,
		);
	}

	/**
	 * Create a new IronJob for a pushed job.
	 *
	 * @param  object  $job
	 * @return \Illuminate\Queue\Jobs\IronJob
	 */
	protected function createPushedIronJob($job)
	{
		return new IronJob($this->container, $this, $job, true);
	}

	/**
	 * Create a payload string from the given job and data.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return string
	 */
	protected function createPayload($job, $data = '', $queue = null)
	{
		$payload = $this->setMeta(parent::createPayload($job, $data), 'attempts', 1);

		return $this->setMeta($payload, 'queue', $this->getQueue($queue));
	}

	/**
	 * Parse the job body for firing.
	 *
	 * @param  string  $body
	 * @return string
	 */
	protected function parseJobBody($body)
	{
		return $this->shouldEncrypt ? $this->crypt->decrypt($body) : $body;
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
	 * @return \IronMQ
	 */
	public function getIron()
	{
		return $this->iron;
	}

	/**
	 * Get the request instance.
	 *
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Set the request instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

}
