<?php namespace Illuminate\Events;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Container\Container;

class FireQueuedHandler {

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
	public function fire(Job $job, array $data)
	{
		call_user_func_array(
			[$this->container->make($data['class']), $data['method']], $data['data']
		);

		$job->delete();
	}

}
