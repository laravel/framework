<?php namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

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
	 * The generated key
	 *
	 * @var string
	 */
	protected $newKey = '';

	/**
	 * The old application key
	 *
	 * @var string
	 */
	protected $oldKey = '';

	/**
	 * Files that will be replaced
	 *
	 * @var array
	 */
	protected $paths = [];

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->paths = [
			base_path('.env'),
			base_path('config/app.php'),
		];
		$this->oldKey = $this->laravel['config']['app.key'];
		$this->newKey = $this->getRandomKey();

		if ($this->option('show'))
		{
			return $this->line('<comment>'.$this->newKey.'</comment>');
		}

		$this->replaceKeyInPaths();

		$this->info("Application key [$this->newKey] set successfully.");
	}

	/**
	 * Replace the key in given paths
	 *
	 * @return void
	 */
	protected function replaceKeyInPaths()
	{
		foreach($this->paths as $path) {
			if (file_exists($path))
			{
				file_put_contents($path, str_replace(
					$this->oldKey, $this->newKey, file_get_contents($path)
				));
			}
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
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('show', null, InputOption::VALUE_NONE, 'Simply display the key instead of modifying files.'),
		);
	}

}
