<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class MigratePublishCommand extends Command {

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
	protected $description = "Publish a package's migrations to the application";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$published = $this->laravel['migration.publisher']->publish(
			$this->getSourcePath(), $this->laravel['path'].'/database/migrations'
		);

		foreach ($published as $migration)
		{
			$this->line('<info>Published:</info> '.basename($migration));
		}
	}

	/**
	 * Get the path to the source files (Including vendor and workbench)
	 *
	 * @return string
	 */
	protected function getSourcePath()
	{
		$workbench = $this->laravel['path.base'].'/workbench';
		if (file_exists($workbench)) {
			$path = $workbench.'/'.$this->argument('package').'/src/migrations';
			if (file_exists($path)) {
				return $path;
			}
		}

		$vendor = $this->laravel['path.base'].'/vendor';
		return $vendor.'/'.$this->argument('package').'/src/migrations';
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

}
