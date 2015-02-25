<?php namespace Illuminate\Foundation\Console;

use FilesystemIterator;
use Illuminate\Console\Command;
use League\Flysystem\MountManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use Symfony\Component\Console\Input\InputOption;
use League\Flysystem\Adapter\Local as LocalAdapter;

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
		$paths = ServiceProvider::pathsToPublish(
			$this->option('provider'), $this->option('tag')
		);

		if (empty($paths))
		{
			return $this->comment("Nothing to publish.");
		}

		foreach ($paths as $from => $to)
		{
			if ($this->files->isFile($from))
			{
				$this->publishFile($from, $to);
			}
			elseif ($this->files->isDirectory($from))
			{
				$this->publishDirectory($from, $to);
			}
			else
			{
				$this->error("Can't locate path: <{$from}>");
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
		if ($this->files->exists($to) && ! $this->option('force'))
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
		$manager = new MountManager([
			'from' => new Flysystem(new LocalAdapter($from)),
			'to' => new Flysystem(new LocalAdapter($to)),
		]);

		foreach ($manager->listContents('from://', true) as $file)
		{
			if ($file['type'] === 'file' && ( ! $manager->has('to://'.$file['path']) || $this->option('force')))
			{
				$manager->put('to://'.$file['path'], $manager->read('from://'.$file['path']));
			}
		}

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

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('force', null, InputOption::VALUE_NONE, 'Overwrite any existing files.'),

			array('provider', null, InputOption::VALUE_OPTIONAL, 'The service provider that has assets you want to publish.'),

			array('tag', null, InputOption::VALUE_OPTIONAL, 'The tag that has assets you want to publish.'),
		);
	}

}
