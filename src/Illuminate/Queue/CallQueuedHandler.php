<?php namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Bus\Dispatcher;

class CallQueuedHandler {

	/**
	 * The bus dispatcher implementation
	 *
	 * @var \Illuminate\Contracts\Bus\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Create a new handler instance.
	 *
	 * @param  \Illuminate\Contracts\Bus\Dispatcher
	 * @return void
	 */
	public function __construct(Dispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
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
		$command = $this->setJobInstanceIfNecessary(
			unserialize($data['command'])
		);

		$handler = $this->setJobInstanceIfNecessary(
			$this->dispatcher->resolveHandler($command)
		);

		$method = $this->dispatcher->getHandlerMethod($command);

		call_user_func([$handler, $method], $command);

		if ( ! $job->isDeletedOrReleased())
		{
			$job->delete();
		}
	}

	/**
	 * Set the job instance of the given class if necessary.
	 *
	 * @param  mixed  $instance
	 * @return mixed
	 */
	protected function setJobInstanceIfNecessary($instance)
	{
		if (in_array('Illuminate\Queue\InteractsWithQueue', class_uses_recursive($instance)))
		{
			$instance->setJob($job);
		}

		return $instance;
	}

}
