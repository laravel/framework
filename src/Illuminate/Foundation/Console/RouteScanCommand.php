<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Routing\Annotations\Scanner;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Console\AppNamespaceDetectorTrait;

class RouteScanCommand extends Command {

	use AppNamespaceDetectorTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'route:scan';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Scan a directory for controller annotations';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		file_put_contents($this->getOutputPath(), $this->getRouteDefinitions());

		$this->info('Routes scanned!');
	}

	/**
	 * Get the route definitions for the annotations.
	 *
	 * @return string
	 */
	protected function getRouteDefinitions()
	{
		$provider = 'Illuminate\Foundation\Support\Providers\RouteServiceProvider';

		return '<?php '.PHP_EOL.PHP_EOL.Scanner::create(
			$this->laravel->getProvider($provider)->scans()
		)->getRouteDefinitions().PHP_EOL;
	}

	/**
	 * Get the path to which the routes should be written.
	 *
	 * @return string
	 */
	protected function getOutputPath()
	{
		return $this->laravel['path.storage'].'/framework/routes.scanned.php';
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		$namespace = $this->getAppNamespace().'Http\Controllers';

		return [
			['namespace', null, InputOption::VALUE_OPTIONAL, 'The root namespace for the controllers.', $namespace],

			['path', null, InputOption::VALUE_OPTIONAL, 'The path to scan.', 'Http'.DIRECTORY_SEPARATOR.'Controllers'],
		];
	}

}
