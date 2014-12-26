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
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$key = $this->getRandomKey();

		foreach ([base_path('.env'), base_path('.env.example')] as $path)
		{
			if (file_exists($path))
			{
				file_put_contents($path, str_replace(
					$this->laravel['config']['app.key'], $key, file_get_contents($path)
				));
			}
		}

		$this->laravel['config']['app.key'] = $key;

		if ( ! $this->input->getOption('pretend'))
		{
			$this->info("Application key [$key] set successfully.");
		}
		else
		{
			$this->comment($key);
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
			array('pretend', null, InputOption::VALUE_NONE, 'Generate key without updating the config file.'),
		);
	}

}
