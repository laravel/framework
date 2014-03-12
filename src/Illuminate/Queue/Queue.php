<?php namespace Illuminate\Queue;

use Closure;
use DateTime;
use Illuminate\Container\Container;
use Illuminate\Support\SerializableClosure;
use Illuminate\Support\Facades\Config;

abstract class Queue {

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * Marshal a push queue request and fire the job.
	 *
	 * @throws \RuntimeException
	 */
	public function marshal()
	{
		throw new \RuntimeException("Push queues only supported by Iron.");
	}

	/**
	 * The base subscribe function just throws a runtime exception to let the user know it's not supported.
	 *  Derived classes that support push queues are required to implement an overriding subscribe function.
	 *
	 * @param string  $queue
	 * @param string  $endpoint
	 * @param array   $options
	 * @param array   $advanced
	 * @throws \RuntimeException
	 */
	public function subscribe($queue, $endpoint, array $options = array(), array $advanced = array())
	{
		throw new \RuntimeException("The default queue driver '".Config::get('queue.default')."' doesn't support the subscribe command.");
	}

	/**
	 * This base unsubscribe function just throws a runtime exception to let the user know it's not supported.
	 *  Derived classes that support push queues are required to implement an overriding unsubscribe function.
	 *
	 * @param string  $queue
	 * @param string  $endpoint
	 * @throws \RuntimeException
	 */
	public function unsubscribe($queue, $endpoint)
	{
		throw new \RuntimeException("The default queue driver '".Config::get('queue.default')."' doesn't support the unsubscribe command.");
	}

	/**
	 * The base update function just throws a runtime exception to let the user know it's not supported.
	 *  Derived classes that support push queues are required to implement an overriding subscribe function.
	 *
	 * @param string  $queue
	 * @param string  $endpoint
	 * @param array   $options
	 * @param array   $advanced
	 * @throws \RuntimeException
	 */
	public function update($queue, $endpoint, array $options = array(), array $advanced = array())
	{
		throw new \RuntimeException("The default queue driver '".Config::get('queue.default')."' doesn't support the update command.");
	}

	/**
	 * Push a new an array of jobs onto the queue.
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
		else
		{
			return json_encode(array('job' => $job, 'data' => $data));
		}
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
		$closure = serialize(new SerializableClosure($job));

		return array('job' => 'IlluminateQueueClosure', 'data' => compact('closure'));
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
		else
		{
			return intval($delay);
		}
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

}
