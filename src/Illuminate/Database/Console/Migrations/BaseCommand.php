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
		return $path ? $this->laravel->basePath().'/'.$path : $this->laravel->databasePath().'/migrations';
	}

}
