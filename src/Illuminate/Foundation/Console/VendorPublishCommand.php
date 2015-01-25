<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class VendorPublishCommand extends Command {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'vendor:publish';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Publish any publishable assets from vendor packages";

	/**
	 * Create a new command instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem
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
		foreach (ServiceProvider::pathsToPublish() as $from => $to)
		{
			if ($this->files->isFile($from))
			{
				$this->publishFile($from, $to);
			}
			elseif ($this->files->isDirectory($from))
			{
				$this->publishDirectory($from, $to);
			}
		}

		$this->info('Publishing Complete!');
	}

	/**
	 * Publish the file to the given path.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return void
	 */
	protected function publishFile($from, $to)
	{
		if ($this->files->exists($to))
		{
			return;
		}

		$this->createParentDirectory(dirname($to));

		$this->files->copy($from, $to);

		$this->status($from, $to, 'File');
	}

	/**
	 * Publish the directory to the given directory.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return void
	 */
	protected function publishDirectory($from, $to)
	{
		if ($this->files->isDirectory($to))
		{
			return;
		}

		$this->createParentDirectory($to);

		$this->files->copyDirectory($from, $to);

		$this->status($from, $to, 'Directory');
	}

	/**
	 * Create the directory to house the published files if needed.
	 *
	 * @param  string  $directory
	 * @return void
	 */
	protected function createParentDirectory($directory)
	{
		if ( ! $this->files->isDirectory($directory))
		{
			$this->files->makeDirectory($directory, 0755, true);
		}
	}

	/**
	 * Write a status message to the console.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @param  string  $type
	 * @return void
	 */
	protected function status($from, $to, $type)
	{
		$from = str_replace(base_path(), '', realpath($from));

		$to = str_replace(base_path(), '', realpath($to));

		$this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
	}

}
