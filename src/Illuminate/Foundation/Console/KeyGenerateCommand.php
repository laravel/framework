<?php namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

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
	 * Create a new key generator command.
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
		$contents = $this->files->get($path = $this->laravel['path'].'/config/app.php');

		$contents = str_replace('YourSecretKey!!!', $key = $this->getRandomKey(), $contents, $count);

		if ($count == 0)
		{
			$this->comment('Application key has already been set.');
		}
		else
		{
			$this->files->put($path, $contents);

			$this->info("Application key [$key] set successfully.");
		}
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
