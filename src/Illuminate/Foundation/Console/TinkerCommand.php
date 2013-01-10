<?php namespace Illuminate\Foundation\Console;

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
		$input = $this->prompt();

		while ($input != 'quit')
		{
			// We will wrap the execution of the command in a try / catch block so we
			// can easily display the errors in a convenient way instead of having
			// them bubble back out to the CLI and stop the entire command loop.
			try
			{
				if (starts_with($input, 'dump '))
				{
					$input = 'var_dump('.substr($input, 5).');';
				}

				eval($input);
			}

			// If an exception occurs, we will just display the message and keep this
			// loop going so we can keep executing commands. However, when a fatal
			// a error occurs we have no choice but to bail out of the routines.
			catch (\Exception $e)
			{
				$this->error($e->getMessage());
			}

			$input = $this->prompt();
		}
	}

	/**
	 * Prompt the developer for a command.
	 *
	 * @return string
	 */
	protected function prompt()
	{
		$dialog = $this->getHelperSet()->get('dialog');

		return $dialog->ask($this->output, "<info>></info>", null);
	}

}