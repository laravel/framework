<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class VendorAssetsCommand extends Command {

	/**
	 * The filesystem instance.
	 *
	 * @var \Symfony\Component\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'vendor:assets';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Copy/symlink vendor assets to the public folder";

	/**
	 * Create a new command instance.
	 *
	 * @param  \Symfony\Component\Filesystem\Filesystem  $files
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
		$targetDir = public_path('vendor');
		$this->files->mkdir($targetDir, 0777);

		$this->info('Starting to copy/symlink the assets to the public folder');

		foreach (ServiceProvider::assetsToPublish() as $from => $package)
		{
			$to = $targetDir . '/' . basename($package);
			$this->files->remove($to);

			$relativeFrom = $this->files->makePathRelative(realpath($from), $targetDir);

			try
			{
				// Try to symlink, otherwise mirror
				$this->files->symlink($relativeFrom, $to, true);
				if ( ! file_exists($to))
				{
					throw new IOException('Symbolic link is broken');
				}
			}
			catch (IOException $e)
			{
				$this->files->mirror($from, $to);
			}

			if (file_exists($to))
			{
				$this->status($from, $to);
			}
		}

		$this->info('Done publishing the assets!');
	}

	/**
	 * Write a status message to the console.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return void
	 */
	protected function status($from, $to)
	{
		$from = str_replace(base_path(), '', realpath($from));

		if (is_link($to))
		{
			$to = str_replace(base_path(), '', $to);
			$this->line('<info>Symlinked directory</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
		}
		else
		{
			$to = str_replace(base_path(), '', realpath($to));
			$this->line('<info>Copied directory</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
		}

	}

}
