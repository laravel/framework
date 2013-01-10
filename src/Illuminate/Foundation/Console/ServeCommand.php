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

		$port = $this->input->getOption('port');

		$this->info("Laravel development server started on port {$port}...");

		passthru("php -S localhost:{$port} -t public server.php");
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on.', 8000),
		);
	}

}