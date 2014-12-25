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
		$command = unserialize($data['command']);

		$handler = $this->dispatcher->resolveHandler($command);

		if (method_exists($handler, 'setJob'))
		{
			$handler->setJob($job);
		}

		$method = $this->dispatcher->getHandlerMethod($command);

		call_user_func([$handler, $method], $command);

		if ( ! $job->isDeletedOrReleased())
		{
			$job->delete();
		}
	}

}
