<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class ListFailedCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:failed';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'List all of the failed queue jobs';

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
		$rows = array();

		foreach ($this->failer->all() as $failed)
		{
			$rows[] = array_values(array_except((array) $failed, array('payload')));
		}

		if (count($rows) == 0)
		{
			return $this->info('Awesome! No failed jobs!');
		}

		$table = $this->getHelperSet()->get('table');

		$table->setHeaders(array('ID', 'Connection', 'Queue', 'Failed At'))
              ->setRows($rows)
              ->render($this->output);
	}

}