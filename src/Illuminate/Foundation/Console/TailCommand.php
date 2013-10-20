<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class TailCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'tail';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Tail a log file on a remote server";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$path = $this->getPath();

		if ($path)
		{
			$output = $this->output;

			$this->getConnection()->run('tail -f '.$path, function($line) use ($output)
			{
				$output->write($line);
			});
		}
		else
		{
			$this->error('Could not determine path to log file.');
		}
	}

	/**
	 * Get a connection to the remote server.
	 *
	 * @return \Illuminate\Remote\Connection
	 */
	protected function getConnection()
	{
		return $this->laravel['remote']->connection($this->argument('connection'));
	}

	/**
	 * Get the path to the Laraevl log file.
	 *
	 * @return string
	 */
	protected function getPath()
	{
		if ($this->option('path')) return $this->option('path');

		return $this->getRoot($this->argument('connection')).'/app/storage/logs/laravel.log';
	}

	/**
	 * Get the path to the Laravel install root.
	 *
	 * @param  string  $connection
	 * @return string
	 */
	protected function getRoot($connection)
	{
		return $this->laravel['config']['remote.connections.'.$connection.'.root'];
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('connection', InputArgument::REQUIRED, 'The remote connection name'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('path', null, InputOption::VALUE_OPTIONAL, 'The fully qualified path to the log file.'),
		);
	}

}