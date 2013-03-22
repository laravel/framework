<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Migrations\MigrationCreator;

class MakeCommand extends BaseCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:make';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new migration file';

	/**
	 * The migration creator instance.
	 *
	 * @var Illuminate\Database\Migrations\MigrationCreator
	 */
	protected $creator;

	/**
	 * The path to the packages directory (vendor).
	 *
	 * @var string
	 */
	protected $packagePath;

	/**
	 * Create a new migration install command instance.
	 *
	 * @param  Illuminate\Database\Migrations\MigrationCreator  $creator
	 * @param  string  $packagePath
	 * @return void
	 */
	public function __construct(MigrationCreator $creator, $packagePath)
	{
		parent::__construct();

		$this->creator = $creator;
		$this->packagePath = $packagePath;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		// It's possible for the developer to specify the tables to modify in this
		// schema operation. The developer may also specify if this table needs
		// to be freshly created so we can create the appropriate migrations.
		$name = $this->input->getArgument('name');

		$table = $this->input->getOption('table');

		$create = $this->input->getOption('create');

		// Now we're ready to get the path where these migrations should be placed
		// on disk. This may be specified via the package option on the command
		// and we will verify that option to determine the appropriate paths.
		$this->writeMigration($name, $table, $create);

		$this->info('Migration created successfully!');
	}

	/**
	 * Write the migration file to disk.
	 *
	 * @param  string  $name
	 * @param  string  $table
	 * @param  bool    $create
	 * @return void
	 */
	protected function writeMigration($name, $table, $create)
	{
		$path = $this->getMigrationPath();

		$this->creator->create($name, $path, $table, $create);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The name of the migration'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('bench', null, InputOption::VALUE_OPTIONAL, 'The workbench the migration belongs to.', null),

			array('create', null, InputOption::VALUE_NONE, 'The table needs to be created.'),

			array('package', null, InputOption::VALUE_OPTIONAL, 'The package the migration belongs to.', null),

			array('path', null, InputOption::VALUE_OPTIONAL, 'Where to store the migration.', null),

			array('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate.'),
		);
	}

}