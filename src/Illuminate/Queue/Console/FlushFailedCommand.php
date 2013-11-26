<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class FlushFailedCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:flush';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Flush all of the failed queue jobs';

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
		$this->failer->flush();

		$this->info('All failed jobs deleted!');
	}

}