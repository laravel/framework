<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

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
		$this->migrator->setConnection($this->input->getOption('database'));

		$this->output->writeln(" Status   Migration Name ");
		$this->output->writeln("--------------------------------------------");

		$versions = $this->migrator->getRepository()->getRan();
		$migrationFiles = $this->migrator->getMigrationFiles($this->getMigrationPath());

		foreach ($migrationFiles as $migration)
		{
			if (in_array($migration, $versions))
			{
				$status = "   <info>up</info>  ";
				unset($versions[array_search($migration, $versions)]);
			}
			else
			{
				$status = "  <error>down</error> ";
			}

			$this->output->writeln("{$status}   <comment>{$migration}</comment>");
		}

		foreach ($versions as $missing)
		{
			$this->output->writeln("   <error>up</error>     {$missing} <error>*** MISSING ***</error>");
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
			array('bench', null, InputOption::VALUE_OPTIONAL, 'The name of the workbench to migrate.', null),

			array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),

			array('path', null, InputOption::VALUE_OPTIONAL, 'The path to migration files.', null),

			array('package', null, InputOption::VALUE_OPTIONAL, 'The package to migrate.', null),
		);
	}

}
