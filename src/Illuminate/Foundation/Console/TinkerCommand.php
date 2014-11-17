<?php namespace Illuminate\Foundation\Console;

use Boris\Boris;
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
		if ($this->supportsBoris())
		{
			$this->getApplication()->setCatchExceptions(false);

			(new Boris('> '))->start();
		}
		else
		{
			$this->comment('Boris REPL not supported. Needs readline, posix, and pcntl extensions.');
		}
	}

	/**
	 * Determine if the current environment supports Boris.
	 *
	 * @return bool
	 */
	protected function supportsBoris()
	{
		return extension_loaded('readline') && extension_loaded('posix') && extension_loaded('pcntl');
	}

}
