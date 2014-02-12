<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

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
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$rows = array();

		foreach ($this->laravel['queue.failer']->all() as $failed)
		{
			$rows[] = array_values(array_except((array) $failed, array('payload')));
		}

		if (count($rows) == 0)
		{
			return $this->info('No failed jobs!');
		}

		$table = $this->getHelperSet()->get('table');

		$table->setHeaders(array('ID', 'Connection', 'Queue', 'Failed At'))
              ->setRows($rows)
              ->render($this->output);
	}

}