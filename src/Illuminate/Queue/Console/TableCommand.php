<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Illuminate\Filesystem\Filesystem;

class TableCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:table';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a migration for the queue jobs database table';

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * @var \Illuminate\Foundation\Composer
	 */
	protected $composer;

	/**
	 * Create a new queue job table command instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files, Composer $composer)
	{
		parent::__construct();

		$this->files = $files;
		$this->composer = $composer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$fullPath = $this->createBaseMigration();

		$this->files->put($fullPath, $this->files->get(__DIR__.'/stubs/jobs.stub'));

		$this->info('Migration created successfully!');

		$this->composer->dumpAutoloads();
	}

	/**
	 * Create a base migration file for the table.
	 *
	 * @return string
	 */
	protected function createBaseMigration()
	{
		$name = 'create_jobs_table';

		$path = $this->laravel['path.database'].'/migrations';

		return $this->laravel['migration.creator']->create($name, $path);
	}

}
