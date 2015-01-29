<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\Tinker\Presenters\EloquentCollectionPresenter;
use Illuminate\Foundation\Console\Tinker\Presenters\EloquentModelPresenter;
use Illuminate\Foundation\Console\Tinker\Presenters\IlluminateApplicationPresenter;
use Psy\Configuration;
use Psy\Shell;
use Symfony\Component\Console\Input\InputArgument;

class TinkerCommand extends Command {

	private static $commandWhitelist = [
		'clear-compiled', 'down', 'env', 'inspire', 'migrate', 'optimize', 'up',
	];

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'tinker';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Interact with your application";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->getApplication()->setCatchExceptions(false);

		$config = new Configuration();

		$pm = $config->getPresenterManager();
		$pm->addPresenters($this->getPresenters());

		$shell = new Shell($config);
		$shell->addCommands($this->getCommands());
		$shell->setIncludes($this->argument('include'));

		$shell->run();
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['include', InputArgument::IS_ARRAY, 'Include file(s) before starting tinker'],
		];
	}

	/**
	 * Get artisan commands to pass through to PsySH.
	 *
	 * @return array
	 */
	protected function getCommands()
	{
		$app = $this->getApplication();
		$commands = [];

		foreach ($app->all() as $name => $command) {
			if (in_array($name, self::$commandWhitelist)) {
				$commands[] = $command;
			}
		}

		return $commands;
	}

	/**
	 * Get an array of Laravel-specific Presenters.
	 *
	 * @return array
	 */
	protected function getPresenters()
	{
		return [
			new EloquentModelPresenter(),
			new EloquentCollectionPresenter(),
			new IlluminateApplicationPresenter(),
		];
	}
}
