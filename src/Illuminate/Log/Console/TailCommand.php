<?php namespace Illuminate\Log\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class TailCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'log:tail';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Watch the tail of a log file for changes';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		switch(config('app.log')) {
			case 'daily':
				$date = strtotime($this->input->getOption('date'));
				$filepath = $this->getDailyLogfile($date);
				break;
			case 'single':
				$filepath = $this->getLogPath();
				break;
			default:
				$this->error(config('app.log')." not supported.");
				return;
		}

		if(!realpath($filepath)) {
			$this->error("$filepath does not exist");
			return;
		}

		$lines = $this->input->getOption('lines');
		passthru("tail -F -n{$lines} {$filepath}");
	}

	/**
	 * @param string $date
	 *
	 * @return string
	 */
	protected function getDailyLogfile($date = null)
	{
		$filepath = $this->getLogPath();

		$folder = pathinfo($filepath, PATHINFO_DIRNAME);
		$filename = pathinfo($filepath, PATHINFO_FILENAME);
		$ext = pathinfo($filepath, PATHINFO_EXTENSION);

		$date = date('Y-m-d', $date);

		return "{$folder}".DIRECTORY_SEPARATOR."{$filename}-{$date}.{$ext}";
	}

	/**
	 * @return string
	 */
	protected function getLogPath()
	{
		return config('log.path', storage_path('/logs/laravel.log'));
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('date', null, InputOption::VALUE_OPTIONAL, 'The date of the log file to watch (strtotime-compatible)', 'today'),
			array('lines', null, InputOption::VALUE_OPTIONAL, 'The number of lines to watch', 10)
		);
	}
}