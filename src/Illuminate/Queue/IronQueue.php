<?php namespace Illuminate\Queue;

use IronMQ;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Queue\Jobs\IronJob;
use Illuminate\Encryption\Encrypter;

class IronQueue extends Queue implements QueueInterface {

	/**
	 * The IronMQ instance.
	 *
	 * @var IronMQ
	 */
	protected $iron;

	/**
	 * The encrypter instance.
	 *
	 * @var \Illuminate\Encryption\Encrypter
	 */
	protected $crypt;

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
	 * @param  \Illuminate\Encryption\Encrypter  $crypt
	 * @param  \Illuminate\Http\Request  $request
	 * @param  string  $default
	 * @return void
	 */
	public function __construct(IronMQ $iron, Encrypter $crypt, Request $request, $default)
	{
		$this->iron = $iron;
		$this->crypt = $crypt;
		$this->request = $request;
		$this->default = $default;
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
		return $this->later(0, $job, $data, $queue);
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @param  string  $job
	 * @param  mixed  data
	 * @param  string  $queue
	 * @return mixed
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		$delay = $this->getSeconds($delay);

		$payload = $this->createPayload($job, $data);

		return $this->iron->postMessage($this->getQueue($queue), $payload, compact('delay'))->id;
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
			$job->body = $this->crypt->decrypt($job->body);

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
		$this->createPushedIronJob($this->marshalPushedJob(), $this->getQueuePushed())->fire();

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

		$body = $this->crypt->decrypt($r->getContent());

		return (object) array(
			'id' => $r->header('iron-message-id'), 'body' => $body, 'pushed' => true,
		);
	}

	/**
	 * Create a new IronJob for a pushed job.
	 *
	 * @param  \StdClass  $job
	 * @param  string     $queue
	 * @return \Illuminate\Queue\Jobs\IronJob
	 */
	protected function createPushedIronJob($job, $queue)
	{
		return new IronJob($this->container, $this->iron, $job, $queue, true);
	}

	/**
	 * Create a payload string from the given job and data.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @return string
	 */
	protected function createPayload($job, $data = '')
	{
		return $this->crypt->encrypt(parent::createPayload($job, $data));
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
	 * Get the queue for a pushed job.
	 *
	 * @return string
	 */
	protected function getQueuePushed()
	{
		// We want to parse the URL and get the queue that this message was pushed from. The URL
		// is in the format https://{mq-host}/1/projects/{project-id}/queues/{queue-name}...
		// but all we care about is the queue name, so that's what we'll get here.
		$url = parse_url($this->request->header('Iron-Subscriber-Message-Url'));

		$fragments = explode('/', $url['path']);

		return array_get($fragments, 5, $this->default);
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