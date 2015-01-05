<?php namespace Illuminate\Cache\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;

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
	protected $description = "Flush the application cache";

	/**
	 * The cache manager instance.
	 *
	 * @var \Illuminate\Cache\CacheManager
	 */
	protected $cache;

	/**
	 * Create a new cache clear command instance.
	 *
	 * @param  \Illuminate\Cache\CacheManager  $cache
	 * @return void
	 */
	public function __construct(CacheManager $cache)
	{
		parent::__construct();

		$this->cache = $cache;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->laravel['events']->fire('cache:clearing');

		$this->cache->flush();

		$this->laravel['events']->fire('cache:cleared');

		$this->info('Application cache cleared!');
	}

}
