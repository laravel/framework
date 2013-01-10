<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class RefreshCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:refresh';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Reset and re-run all migrations';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$database = $this->input->getOption('database');

		$this->call('migrate:reset', array('--database' => $database));

		// The refresh command is essentially just a brief aggregate of a few other of
		// the migration commands and just provides a convenient wrapper to execute
		// them in succession. We'll also see if we need to res-eed the database.
		$this->call('migrate', array('--database' => $database));

		if ($this->input->getOption('seed'))
		{
			$this->call('db:seed', array('--database' => $database));
		}
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),

			array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
		);
	}

}