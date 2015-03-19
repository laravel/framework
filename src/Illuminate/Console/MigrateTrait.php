<?php namespace Illuminate\Console;

trait MigrateTrait {

	/**
	 * The migrator instance.
	 *
	 * @var \Illuminate\Database\Migrations\Migrator
	 */
	protected $migrator;

	/**
	 * Select the database if specified.
	 *
	 * @return void
	 */
	protected function selectDatabase()
	{
		$this->migrator->setConnection($this->input->getOption('database'));
	}

}
