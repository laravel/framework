<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class ConfigClearCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'config:clear';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Remove the configuration cache file';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		@unlink($this->laravel->getCachedConfigPath());

		$this->info('Configuration cache cleared!');
	}

}
