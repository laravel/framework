<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Contracts\Database\Migrations\MigrationRepository as MigrationRepositoryContract;

class InstallCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:install';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create the migration repository';

	/**
	 * The repository instance.
	 *
	 * @var \Illuminate\Contracts\Database\Migrations\MigrationRepository
	 */
	protected $repository;

	/**
	 * Create a new migration install command instance.
	 *
	 * @param  \Illuminate\Contracts\Database\Migrations\MigrationRepository  $repository
	 * @return void
	 */
	public function __construct(MigrationRepositoryContract $repository)
	{
		parent::__construct();

		$this->repository = $repository;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->repository->setSource($this->input->getOption('database'));

		$this->repository->createRepository();

		$this->info("Migration table created successfully.");
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
		);
	}

}
