<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\DatabaseTrait;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends BaseCommand {

	use DatabaseTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:status';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Show a list of migrations up/down';

	/**
	 * Create a new migration rollback command instance.
	 *
	 * @param  \Illuminate\Database\Migrations\Migrator $migrator
	 * @return \Illuminate\Database\Console\Migrations\StatusCommand
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
		$this->selectDatabase();

		if ( ! $this->migrator->repositoryExists())
		{
			return $this->error('No migrations found.');
		}

		$ran = $this->migrator->getRepository()->getRan();

		$migrations = [];

		foreach ($this->getAllMigrationFiles() as $migration)
		{
			$migrations[] = in_array($migration, $ran) ? ['<info>Y</info>', $migration] : ['<fg=red>N</fg=red>', $migration];
		}

		if (count($migrations) > 0)
		{
			$this->table(['Ran?', 'Migration'], $migrations);
		}
		else
		{
			$this->error('No migrations found');
		}
	}

	/**
	 * Get all of the migration files.
	 *
	 * @return array
	 */
	protected function getAllMigrationFiles()
	{
		return $this->migrator->getMigrationFiles($this->getMigrationPath());
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
		];
	}

}
