<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\MigrationPublisher;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PublishCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:publish';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Copy a package\'s migration file into your app.';

	/**
	 * The publisher instance.
	 *
	 * @var \Illuminate\Database\Migrations\MigrationPublisher
	 */
	protected $publisher;

	/**
	 * The path in which packages are located.
	 *
	 * @var string
	 */
	protected $packagePath;

	/**
	 * The path migrations should be published to.
	 *
	 * @var string
	 */
	protected $migrationsPath;

	/**
	 * Create a new migrate publish command instance.
	 *
	 * @param \Illuminate\Database\Migrations\MigrationPublisher  $publisher
	 */
	public function __construct(MigrationPublisher $publisher, $packagePath, $migrationsPath)
	{
		parent::__construct();
		$this->publisher = $publisher;
		$this->packagePath = $packagePath;
		$this->migrationsPath = $migrationsPath;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$sourcePath = $this->getMigrationSourcePath();
		$this->publisher->setSourcePath($sourcePath);

		if (!$this->publisher->sourceHasMigrations())
		{
			$this->error('No migrations found in '. $sourcePath);
			return;
		}

		$destPath = $this->getDestinationPath();
		$this->publisher->setDestinationPath($destPath);

		$count = 0;
		$files = $this->publisher->getSourceFiles();

		foreach ($files as $file)
		{
			if (!$this->publisher->validMigrationName($file) && $this->option('force') === false)
			{
				$this->error("$file is not a valid migration file name - add the option --force to publish it anyway.");
				continue;
			}

			if ($this->publisher->migrationExists($file) && $this->option('duplicate') === false)
			{
				$this->error("Skipping $file as it already exists - add the option --duplicate to publish it anyway.");
				continue;
			}

			$this->comment("Copying $file...");

			$this->publisher->publish($file);

			$count++;
		}

		$this->info("$count migrations successfully published!");
	}

	/**
	 * Get the path to copy migrations from.
	 *
	 * @return string
	 */
	protected function getMigrationSourcePath()
	{
		$package = $this->argument('package');

		return $this->packagePath.'/'.$package.'/src/migrations';
	}

	/**
	 * Get the path to copy migrations to.
	 *
	 * @return string
	 */
	protected function getDestinationPath()
	{
		$pathOption = $this->option('path');

		if ($pathOption !== null) {
			return $pathOption;
		}

		return $this->migrationsPath;
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('package', InputArgument::REQUIRED, 'The name of the package being published.'),
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
			array('path', null, InputOption::VALUE_OPTIONAL, 'The path migration files are published to.', null),
			array('force', null, InputOption::VALUE_NONE, 'Force misnamed migrations to be published.'),
			array('duplicate', null, InputOption::VALUE_NONE, 'Allow duplicate migrations.'),
		);
	}
}
