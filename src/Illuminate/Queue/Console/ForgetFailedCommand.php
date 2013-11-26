<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\QueueManager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class ForgetFailedCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:forget';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Delete a failed queue job';

	/**
	 * The failed job provider implementation.
	 *
	 * @var \Illuminate\Queue\Failed\FailedJobProviderInterface
	 */
	protected $failer;

	/**
	 * Create a new failed job lister command instance.
	 *
	 * @param  \Illuminate\Queue\Failed\FailedJobProviderInterface  $failer
	 * @return void
	 */
	public function __construct(FailedJobProviderInterface $failer)
	{
		parent::__construct();

		$this->failer = $failer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if ($this->failer->forget($this->argument('id')))
		{
			$this->info('Failed job deleted successfully!');
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