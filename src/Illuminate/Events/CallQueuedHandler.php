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
		call_user_func_array(
			[$this->createHandler($job, $data), $data['method']], unserialize($data['data'])
		);

		if ( ! $job->isDeletedOrReleased())
		{
			$job->delete();
		}
	}

	/**
	 * Create the handler instance for the given event.
	 *
	 * @param  \Illuminate\Contracts\Queue\Job  $job
	 * @param  array  $data
	 * @return mixed
	 */
	protected function createHandler(Job $job, array $data)
	{
		$handler = $this->container->make($data['class']);

		if (method_exists($handler, 'setJob'))
		{
			$handler->setJob($job);
		}

		return $handler;
	}

}
