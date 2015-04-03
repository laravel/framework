<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends Command {

	use ConfirmableTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:reset';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Rollback all database migrations';

	/**
	 * The migrator instance.
	 *
	 * @var \Illuminate\Database\Migrations\Migrator
	 */
	protected $migrator;

	/**
	 * Create a new migration rollback command instance.
	 *
	 * @param  \Illuminate\Database\Migrations\Migrator  $migrator
	 * @return void
	 */
	public function __construct(Migrator $migrator)
	{
		parent::__construct();

		$this->migrator = $migrator;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if ( ! $this->confirmToProceed()) return;

		$this->migrator->setConnection($this->input->getOption('database'));

		$pretend = $this->input->getOption('pretend');

		$this->migrator->reset($pretend);

		// Once the migrator has run we will grab the note output and send it out to
		// the console screen, since the migrator itself functions without having
		// any instances of the OutputInterface contract passed into the class.
		foreach ($this->migrator->getNotes() as $note)
		{
			$this->output->writeln($note);
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

			array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),

			array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
		);
	}

}
