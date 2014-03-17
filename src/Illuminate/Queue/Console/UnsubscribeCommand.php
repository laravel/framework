<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UnsubscribeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:unsubscribe';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Unsubscribe a URL from an Iron.io or SQS push queue';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 *
	 * @throws \RuntimeException
	 */
	public function fire()
	{
		$queue = $this->laravel['queue']->connection();

		$queue->unsubscribe($this->argument('queue'), $this->argument('url'));

		$this->line('<info>Queue </info><comment>'.$this->argument('queue').'</comment><info> unsubscribed from:</info> <comment>'.$this->argument('url').'</comment>');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('queue', InputArgument::REQUIRED, 'The name of Iron.io queue or SNS topic.'),

			array('url', InputArgument::REQUIRED, 'The URL to be unsubscribed.'),
		);
	}

}
