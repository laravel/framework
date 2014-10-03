<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class ClearCompiledCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'clear-compiled';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Remove the compiled class file";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if (file_exists($path = $this->laravel['path.storage'].'/framework/compiled.php'))
		{
			@unlink($path);
		}

		if (file_exists($path = $this->laravel['config']['app.manifest'].'/services.json'))
		{
			@unlink($path);
		}
	}

}
