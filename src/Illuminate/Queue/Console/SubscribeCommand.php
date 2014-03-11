<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SubscribeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:subscribe';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Subscribe a URL to an Iron.io or SQS push queue';

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
		
		$queue->subscribe($this->argument('queue'), $this->argument('url'), array_only($this->option(), array('type', 'retries', 'patience')));

		$this->line('<info>Queue subscriber added:</info> <comment>'.$this->argument('url').'</comment>');
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

			array('url', InputArgument::REQUIRED, 'The URL to be subscribed.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('type', null, InputOption::VALUE_OPTIONAL, 'The push type for the queue.', 'multicast'),

			array('retries', null, InputOption::VALUE_OPTIONAL, 'Number of retries.', 3),

			array('patience', null, InputOption::VALUE_OPTIONAL, 'The number of seconds to wait between retries.', '60'),
		);
	}

}
