<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;

class BaseCommand extends Command {

	/**
	 * Get the path to the migration directory.
	 *
	 * @return string
	 */
	protected function getMigrationPath($path = NULL)
	{
		if (! is_null($path)) {
          return $this->laravel->basePath(). '/'. $path;
        } else {
          return $this->laravel->databasePath().'/migrations';
        }
	}

}
