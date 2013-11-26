<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\QueueManager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class RetryCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:retry';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Retry a failed queue job';

	/**
	 * The failed job provider implementation.
	 *
	 * @var \Illuminate\Queue\Failed\FailedJobProviderInterface
	 */
	protected $failer;

	/**
	 * The queue manager instance.
	 *
	 * @var \Illuminate\Queue\QueueManager
	 */
	protected $queue;

	/**
	 * Create a new failed job lister command instance.
	 *
	 * @param  \Illuminate\Queue\Failed\FailedJobProviderInterface  $failer
	 * @param  \Illuminate\Queue\QueueManager  $queue
	 * @return void
	 */
	public function __construct(FailedJobProviderInterface $failer, QueueManager $queue)
	{
		parent::__construct();

		$this->queue = $queue;
		$this->failer = $failer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$failed = $this->failer->find($this->argument('id'));

		if ( ! is_null($failed))
		{
			$this->queue->connection($failed->connection)->pushRaw($failed->payload, $failed->queue);

			$this->failer->forget($failed->id);

			$this->info('The failed job has been pushed back onto the queue!');
		}
		else
		{
			$this->error('No failed job matches the given ID.');
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('id', InputArgument::REQUIRED, 'The ID of the failed job'),
		);
	}

}