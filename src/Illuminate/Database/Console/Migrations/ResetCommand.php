<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends BaseCommand {

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
	 * @var Illuminate\Database\Migrations\Migrator
	 */
	protected $migrator;

	/**
	 * Create a new migration rollback command instance.
	 *
	 * @param  Illuminate\Database\Migrations\Migrator  $migrator
	 * @return void
	 */
	public function __construct(Migrator $migrator, $packagePath)
	{
		parent::__construct();

		$this->migrator = $migrator;
		$this->packagePath = $packagePath;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->migrator->setConnection($this->input->getOption('database'));

		$path = $this->getMigrationPath();

		$pretend = $this->input->getOption('pretend');

		while (true)
		{
			$count = $this->migrator->rollback($path, $pretend);

			// Once the migrator has run we will grab the note output and send it out to
			// the console screen, since the migrator itself functions without having
			// any instances of the OutputInterface contract passed into the class.
			foreach ($this->migrator->getNotes() as $note)
			{
				$this->output->writeln($note);
			}

			if ($count == 0) break;
		}
	}

}