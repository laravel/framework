<?php namespace Illuminate\Queue;

use Closure;
use DateTime;
use RuntimeException;
use SuperClosure\Serializer;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

abstract class Queue {

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $queue
	 * @param  string  $job
	 * @param  mixed   $data
	 * @return mixed
	 */
	public function pushOn($queue, $job, $data = '')
	{
		return $this->push($job, $data, $queue);
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  string  $queue
	 * @param  \DateTime|int  $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @return mixed
	 */
	public function laterOn($queue, $delay, $job, $data = '')
	{
		return $this->later($delay, $job, $data, $queue);
	}

	/**
	 * Marshal a push queue request and fire the job.
	 *
	 * @throws \RuntimeException
	 */
	public function marshal()
	{
		throw new RuntimeException("Push queues only supported by Iron.");
	}

	/**
	 * Push an array of jobs onto the queue.
	 *
	 * @param  array   $jobs
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return mixed
	 */
	public function bulk($jobs, $data = '', $queue = null)
	{
		foreach ((array) $jobs as $job)
		{
			$this->push($job, $data, $queue);
		}
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
		if ($job instanceof Closure)
		{
			return json_encode($this->createClosurePayload($job, $data));
		}
		elseif (is_object($job))
		{
			return json_encode([
				'job' => 'Illuminate\Queue\CallQueuedHandler@call',
				'data' => ['command' => serialize($job)]
			]);
		}

		return json_encode($this->createPlainPayload($job, $data));
	}

	/**
	 * Create a typical, "plain" queue payload array.
	 *
	 * @param  string  $job
	 * @param  mixed  $data
	 * @return array
	 */
	protected function createPlainPayload($job, $data)
	{
		return ['job' => $job, 'data' => $this->prepareQueueableEntities($data)];
	}

	/**
	 * Prepare any queueable entities for storage in the queue.
	 *
	 * @param  mixed  $data
	 * @return mixed
	 */
	protected function prepareQueueableEntities($data)
	{
		if ($data instanceof QueueableEntity)
		{
			return $this->prepareQueueableEntity($data);
		}

		if (is_array($data))
		{
			array_walk($data, function(&$d) { $d = $this->prepareQueueableEntity($d); });
		}

		return $data;
	}

	/**
	 * Prepare a single queueable entity for storage on the queue.
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	protected function prepareQueueableEntity($value)
	{
		if ($value instanceof QueueableEntity)
		{
			return '::entity::|'.get_class($value).'|'.$value->getQueueableId();
		}

		return $value;
	}

	/**
	 * Create a payload string for the given Closure job.
	 *
	 * @param  \Closure  $job
	 * @param  mixed     $data
	 * @return string
	 */
	protected function createClosurePayload($job, $data)
	{
		$closure = $this->crypt->encrypt((new Serializer)->serialize($job));

		return ['job' => 'IlluminateQueueClosure', 'data' => compact('closure')];
	}

	/**
	 * Set additional meta on a payload string.
	 *
	 * @param  string  $payload
	 * @param  string  $key
	 * @param  string  $value
	 * @return string
	 */
	protected function setMeta($payload, $key, $value)
	{
		$payload = json_decode($payload, true);

		return json_encode(array_set($payload, $key, $value));
	}

	/**
	 * Calculate the number of seconds with the given delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @return int
	 */
	protected function getSeconds($delay)
	{
		if ($delay instanceof DateTime)
		{
			return max(0, $delay->getTimestamp() - $this->getTime());
		}

		return (int) $delay;
	}

	/**
	 * Get the current UNIX timestamp.
	 *
	 * @return int
	 */
	public function getTime()
	{
		return time();
	}

	/**
	 * Set the IoC container instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Set the encrypter instance.
	 *
	 * @param  \Illuminate\Contracts\Encryption\Encrypter  $crypt
	 * @return void
	 */
	public function setEncrypter(EncrypterContract $crypt)
	{
		$this->crypt = $crypt;
	}

}
