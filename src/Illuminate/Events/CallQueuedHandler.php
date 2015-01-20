<?php namespace Illuminate\Events;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Container\Container;

class CallQueuedHandler {

	/**
	 * The container instance.
	 *
	 * @var \Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Contracts\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Handle the queued job.
	 *
	 * @param  \Illuminate\Contracts\Queue\Job  $job
	 * @param  array  $data
	 * @return void
	 */
	public function call(Job $job, array $data)
	{
		$handler = $this->setJobInstanceIfNecessary(
			$job, $this->container->make($data['class'])
		);

		call_user_func_array(
			[$handler, $data['method']], unserialize($data['data'])
		);

		if ( ! $job->isDeletedOrReleased())
		{
			$job->delete();
		}
	}

	/**
	 * Set the job instance of the given class if necessary.
	 *
	 * @param  \Illuminate\Contracts\Queue\Job  $job
	 * @param  mixed  $instance
	 * @return mixed
	 */
	protected function setJobInstanceIfNecessary(Job $job, $instance)
	{
		if (in_array('Illuminate\Queue\InteractsWithQueue', class_uses_recursive(get_class($instance))))
		{
			$instance->setJob($job);
		}

		return $instance;
	}

	/**
	 * Call the failed method on the job instance.
	 *
	 * @return void
	 */
	public function failed(array $data)
	{
		$handler = $this->container->make($data['class']);

		if (method_exists($handler, 'failed'))
		{
			call_user_func_array([$handler, 'failed'], unserialize($data));
		}
	}

}
