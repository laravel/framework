<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\EventCache;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\ClassFinder;

class EventCacheCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'event:cache';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a cache file of all @hears annotation events';

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new event cache command instance.
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
		$cache = (new EventCache(new ClassFinder))->get(
			$this->laravel['config']->get('app.events.scan', [])
		);

		$this->files->put(
			$this->laravel['path.storage'].'/meta/events.php', $cache
		);

		$this->info('Events cached successfully!');
	}

}
