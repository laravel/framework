<?php namespace Illuminate\Cache\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\Filesystem;

class ClearCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cache:clear';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Flush the entire cache.";

	/**
	 * The cache manager instance.
	 *
	 * @var \Illuminate\Cache\CacheManager
	 */
	protected $cache;

	/**
	 * The file system instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The path to the service provider manifest file.
	 *
	 * @var string
	 */
	protected $manifest;

	/**
	 * Create a new cache clear command instance.
	 *
	 * @param  \Illuminate\Cache\CacheManager  $cache
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $manifest
	 * @return void
	 */
	public function __construct(CacheManager $cache, Filesystem $files, $manifest)
	{
		parent::__construct();

		$this->cache = $cache;
		$this->files = $files;
		$this->manifest = $manifest;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->cache->flush();

		$this->files->delete($this->manifest);
	}

}