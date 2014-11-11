<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Database\Migrations\Migrator;

class StatusCommand extends BaseCommand {

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
	 * The migrator instance.
	 *
	 * @var \Illuminate\Database\Migrations\Migrator
	 */
	protected $migrator;

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
		if ( ! $this->migrator->repositoryExists())
		{
			return $this->error('No migrations found.');
		}

		$ran = $this->migrator->getRepository()->getRan();

		$migrations = [];

		foreach ($this->getAllMigrationFiles() as $migration)
		{
			$migrations[] = in_array($migration, $ran) ? ['<info>✔</info>', $migration] : ['<fg=red>✗</fg=red>', $migration];
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
		return $this->migrator->getMigrationFiles($this->laravel['path.database'].'/migrations');
	}

}
