<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class FreshCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'fresh';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Remove some of Laravel's opionated scaffolding";

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new command instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
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
		foreach ($this->getFiles() as $file)
		{
			$this->files->delete($file);

			$this->info('<info>Removed File:</info> '.$file);
		}

		foreach ($this->getDirectories() as $directory)
		{
			$this->files->deleteDirectory($directory);

			$this->info('<comment>Removed Directory:</comment> '.$directory);
		}

		$this->info('Scaffolding Removed!');
	}

	/**
	 * Get the files that should be deleted.
	 *
	 * @return array
	 */
	protected function getFiles()
	{
		return [];
	}

	/**
	 * Get the directories that should be deleted.
	 *
	 * @return array
	 */
	protected function getDirectories()
	{
		return [];
	}

}
