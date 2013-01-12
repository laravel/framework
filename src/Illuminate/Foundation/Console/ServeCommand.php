<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class ServeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'serve';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Serve the application on the PHP development server";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		chdir($this->laravel['path.base']);

		$host = 'localhost';
		if ($this->input->getOption('any-host') === true)
		{
			$host = '0.0.0.0';
		}

		$port = $this->input->getOption('port');

		$this->info("Laravel development server started on {$host}:{$port}...");

		passthru("php -S {$host}:{$port} -t public server.php");
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('any-host', null, InputOption::VALUE_NONE, 'Binds server to 0.0.0.0 for open access'),
			array('port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on.', 8000),
		);
	}

}