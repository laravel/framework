<?php namespace Illuminate\Queue\Jobs;

use IronMQ;
use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;

class IronJob extends Job {

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
	 * The IronMQ message instance.
	 *
	 * @var array
	 */
	protected $job;

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
	 * @param  object  $job
	 * @param  string  $queue
	 * @param  bool    $pushed
	 * @return void
	 */
	public function __construct(Container $container,
                                IronMQ $iron,
                                Encrypter $crypt,
                                $job,
                                $pushed = false)
	{
		$this->job = $job;
		$this->iron = $iron;
		$this->crypt = $crypt;
		$this->pushed = $pushed;
		$this->container = $container;
	}

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->resolveAndFire(json_decode($this->job->body, true));
	}

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		parent::delete();

		if (isset($this->job->pushed)) return;

		$this->iron->deleteMessage($this->getQueue(), $this->job->id);
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		if ( ! $this->pushed) $this->delete();

		$this->recreateJob($delay);
	}

	/**
	 * Release a pushed job back onto the queue.
	 *
	 * @param  int  $delay
	 * @return void
	 */
	protected function recreateJob($delay)
	{
		$this->iron->postMessage($this->getQueue(), $this->createPayload(), array('delay' => $this->getSeconds($delay)));
	}

	/**
	 * Create a payload string from the old payload and increment the attempts.
	 *
	 * @return string
	 */
	protected function createPayload()
	{
		$payload = json_decode($this->job->body, true);

		array_set($payload, 'attempts', array_get($payload, 'attempts', 1) + 1);

		return $this->crypt->encrypt(json_encode($payload));
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	public function attempts()
	{
		return array_get(json_decode($this->job->body, true), 'attempts', 1);
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

	/**
	 * Get the name of the queue the job belongs to.
	 *
	 * @return string
	 */
	public function getQueue()
	{
		return array_get(json_decode($this->job->body, true), 'queue');
	}

}