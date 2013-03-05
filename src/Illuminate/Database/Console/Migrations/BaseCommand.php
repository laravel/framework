<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class BaseCommand extends Command {

	/**
	 * Get the path to the migration directory.
	 *
	 * @return string
	 */
	protected function getMigrationPath()
	{
		$path = $this->input->getOption('path');

		// First, we will check to see if a path option has been defined. If it has
		// we will use the path relative to the root of this installation folder
		// so that migrations may be run for any path within the applications.
		if ( ! is_null($path))
		{
			return $this->laravel['path.base'].'/'.$path;
		}

		$package = $this->input->getOption('package');

		// If the package is in the list of migration paths we received we will put
		// the migrations in that path. Otherwise, we will assume the package is
		// is in the package directories and will place them in that location.
		if ( ! is_null($package))
		{
			return $this->packagePath.'/'.$package.'/src/migrations';
		}

		$bench = $this->input->getOption('bench');

		// Finally we will check for the workbench option, which is a shortcut into
		// specifying the full path for a "workbench" project. Workbenches allow
		// developers to develop packages along side a "standard" app install.
		if ( ! is_null($bench))
		{
			$path = "/workbench/{$bench}/src/migrations";

			return $this->laravel['path.base'].$path;
		}

		return $this->laravel['path'].'/database/migrations';
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

			array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
		);
	}

}