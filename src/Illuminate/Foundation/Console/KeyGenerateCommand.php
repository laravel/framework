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
		$original_key = $this->laravel['config']['app.key'];

		$environment_key = $this->getRandomKey();

		foreach(glob(base_path()."/.env*") as $path)
		{
			$this->setEnvKey($environment_key, $path, $original_key);
			$this->info("Environment application key [$environment_key] set successfully for $path.");
			$this->laravel['config']['app.key'] = $environment_key;
		}

		if ($original_key === 'SomeRandomString')
		{
			$default_key = $this->getRandomKey();
			$this->setEnvKey($default_key, base_path($this->laravel['path.config'].'/app.php'), $original_key);
			$this->info("Default application key [$default_key] set successfully for ".base_path($this->laravel['path.config'].'/app.php'));
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

	/**
	 * Set random key for the application.
	 *
	 * @param  string  $key
	 * @param  string  $path
	 * @param  string  $original_key
	 * @return boolean
	 */
	protected function setEnvKey($key, $path, $original_key)
	{
		if (file_exists($path))
		{
			file_put_contents($path, str_replace(
				$original_key, $key, file_get_contents($path)
			));
		}

		return true;
	}
}
