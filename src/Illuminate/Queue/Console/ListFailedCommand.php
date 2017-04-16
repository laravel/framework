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
			$rows[] = $this->parseFailedJob((array) $failed);
		}

		if (count($rows) == 0)
		{
			return $this->info('No failed jobs!');
		}

		$table = $this->getHelperSet()->get('table');

		$table->setHeaders(array('ID', 'Connection', 'Queue', 'Class', 'Payload', 'Error Message', 'Failed At'))
              ->setRows($rows)
              ->render($this->output);
	}

	/**
	 * Parse the failed job row.
	 *
	 * @param  array  $failed
	 * @return array
	 */
	protected function parseFailedJob(array $failed)
	{
		return array(
			'id' => $failed['id'],
			'connection' => $failed['connection'],
			'queue' => $failed['queue'],
			'class' => array_get(json_decode($failed['payload'], true), 'job'),
			'payload' => $failed['payload'],
			'message' => $failed['message'],
			'failed_at' => $failed['failed_at']
		);
	}

}
