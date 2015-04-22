<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;

class BaseCommand extends Command {

	/**
	 * Get the path to the migration directory.
	 *
	 * @return string
	 */
	protected function getMigrationPath()
	{
		return $this->laravel->databasePath().'/migrations';
	}

    /**
     * Allow facade use for migration commands.
     *
     * @return void
     */
    protected function allowFacades()
    {
        if(method_exists($this->laravel, 'withFacades'))
        {
            $this->laravel->withFacades();
        }
    }

}
