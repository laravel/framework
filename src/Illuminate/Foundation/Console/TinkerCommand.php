<?php namespace Illuminate\Foundation\Console;

use Psy\Shell;
use Illuminate\Console\Command;

class TinkerCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'tinker';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Interact with your application";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->getApplication()->setCatchExceptions(false);

		$shell = new Shell();

		$shell->run();
	}

}
