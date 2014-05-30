<?php namespace Illuminate\Session\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemInterface;

class SessionTableCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'session:table';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a migration for the session database table';

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\FilesystemInterface
	 */
	protected $files;

	/**
	 * Create a new session table command instance.
	 *
	 * @param  \Illuminate\Filesystem\FilesystemInterface  $files
	 * @return void
	 */
	public function __construct(FilesystemInterface $files)
	{
		parent::__construct();

		$this->files = $files;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$fullPath = $this->createBaseMigration();

		$this->files->put($fullPath, $this->files->get(__DIR__.'/stubs/database.stub'));

		$this->info('Migration created successfully!');
	}

	/**
	 * Create a base migration file for the session.
	 *
	 * @return string
	 */
	protected function createBaseMigration()
	{
		$name = 'create_session_table';

		$path = $this->laravel['path'].'/database/migrations';

		return $this->laravel['migration.creator']->create($name, $path);
	}

}
