<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Events\Annotations\Scanner;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Console\AppNamespaceDetectorTrait;

class EventScanCommand extends Command {

	use AppNamespaceDetectorTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'event:scan';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Scan a directory for event annotations';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		file_put_contents($this->getOutputPath(), $this->getEventDefinitions());

		$this->info('Events scanned!');
	}

	/**
	 * Get the route definitions for the annotations.
	 *
	 * @return string
	 */
	protected function getEventDefinitions()
	{
		$provider = 'Illuminate\Foundation\Support\Providers\EventServiceProvider';

		return '<?php '.PHP_EOL.PHP_EOL.Scanner::create(
			$this->laravel->getProvider($provider)->scans()
		)->getEventDefinitions().PHP_EOL;
	}

	/**
	 * Get the path to which the routes should be written.
	 *
	 * @return string
	 */
	protected function getOutputPath()
	{
		return $this->laravel['path.storage'].'/framework/events.scanned.php';
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['path', null, InputOption::VALUE_OPTIONAL, 'The path to scan.'],
		];
	}

}
