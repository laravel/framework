<?php namespace Illuminate\Queue;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\SerializableClosure;

abstract class Queue {

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Container
	 */
	protected $container;

	/**
	 * Marshal a push queue request and fire the job.
	 *
	 * @return Illuminate\Http\Response
	 */
	public function marshal()
	{
		throw new \RuntimeException("Push queues only supported by Iron.");
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
	 * @param  mixed  $data
	 * @return string
	 */
	protected function createClosurePayload($job, $data)
	{
		$closure = serialize(new SerializableClosure($job));

		return array('job' => 'IlluminateQueueClosure', 'data' => compact('closure'));
	}

	/**
	 * Set the IoC container instance.
	 *
	 * @param  \Illuminate\Container  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

}