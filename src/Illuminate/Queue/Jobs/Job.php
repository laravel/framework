<?php namespace Illuminate\Queue\Jobs;

abstract class Job {

	/**
	 * The job handler instance.
	 *
	 * @var mixed
	 */
	protected $instance;

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	abstract public function fire();

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	abstract public function delete();

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	abstract public function release($delay = 0);

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	abstract public function attempts();

	/**
	 * Resolve and fire the job handler method.
	 *
	 * @param  array  $payload
	 * @return void
	 */
	protected function resolveAndFire(array $payload)
	{
		list($class, $method) = $this->parseJob($payload['job']);

		$this->instance = $this->resolve($class);

		$this->instance->{$method}($this, $payload['data']);
	}

	/**
	 * Resolve the given job handler.
	 *
	 * @param  string  $class
	 * @return mixed
	 */
	protected function resolve($class)
	{
		return $this->container->make($class);
	}

	/**
	 * Parse the job declaration into class and method.
	 *
	 * @param  string  $job
	 * @return array
	 */
	protected function parseJob($job)
	{
		$segments = explode('@', $job);

		return count($segments) > 1 ? $segments : array($segments[0], 'fire');
	}

	/**
	 * Determine if job should be auto-deleted.
	 *
	 * @return bool
	 */
	public function autoDelete()
	{
		return isset($this->instance->delete);
	}

}