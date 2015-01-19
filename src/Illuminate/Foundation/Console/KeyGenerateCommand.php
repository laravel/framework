<?php namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class KeyGenerateCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'key:generate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Set the application key";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$key = $this->getRandomKey();

		$environmentFile = base_path($this->laravel->environmentFile());

		if (file_exists($environmentFile))
		{
			file_put_contents($environmentFile, str_replace(
				$this->laravel['config']['app.key'], $key, file_get_contents($environmentFile)
			));
		}

		$this->laravel['config']['app.key'] = $key;

		$this->info("Application key [$key] set successfully.");
	}

	/**
	 * Generate a random key for the application.
	 *
	 * @return string
	 */
	protected function getRandomKey()
	{
		return Str::random(32);
	}

}
