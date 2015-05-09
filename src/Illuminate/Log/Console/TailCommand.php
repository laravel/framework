<?php namespace Illuminate\Log\Console;

use Illuminate\Console\Command;

class TailCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'log:tail';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Watch the tail of the log file for changes';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		dd('hi!');
	}
}
