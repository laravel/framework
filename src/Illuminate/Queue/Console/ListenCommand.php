<?php namespace Illuminate\Queue\Console;

use Illuminate\Queue\Listener;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ListenCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:listen';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Listen to a given queue';

	/**
	 * The queue listener instance.
	 *
	 * @var Illuminate\Queue\Listener
	 */
	protected $listener;

	/**
	 * Create a new queue listen command.
	 *
	 * @param  Illuminate\Queue\Listener  $listener
	 * @return void
	 */
	public function __construct(Listener $listener)
	{
		parent::__construct();

		$this->listener = $listener;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$queue = $this->input->getOption('queue');

		$delay = $this->input->getOption('delay');

		// The memory limit is the amount of memory we will allow the script to occupy
		// before killing it and letting a process manager restart it for us, which
		// is to protect us against any memory leaks that will be in the scripts.
		$memory = $this->input->getOption('memory');

		$connection = $this->input->getArgument('connection');

		$this->listener->listen($connection, $queue, $delay, $memory);
	}

	/**
	 * Listen to the given queue connection.
	 *
	 * @param  string  $connection
	 * @param  string  $queue
	 * @param  int     $delay
	 * @param  int     $memory
	 * @return void
	 */
	public function listen($connection, $queue, $delay, $memory)
	{

	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		$default = $this->laravel['config']['queue.default'];

		return array(
			array('connection', InputArgument::OPTIONAL, 'The name of connection', $default),
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
			array('queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on', 'default'),

			array('delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0),

			array('memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128),
		);
	}

}