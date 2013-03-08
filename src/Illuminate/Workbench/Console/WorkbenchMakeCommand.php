<?php namespace Illuminate\Workbench\Console;

use Illuminate\Console\Command;
use Illuminate\Workbench\Package;
use Illuminate\Workbench\PackageCreator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class WorkbenchMakeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'workbench';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new package workbench';

	/**
	 * The package creator instance.
	 *
	 * @var Illuminate\Workbench\PackageCreator
	 */
	protected $creator;

	/**
	 * Create a new make workbench command instance.
	 *
	 * @param  Illuminate\Workbench\PackageCreator  $creator
	 * @return void
	 */
	public function __construct(PackageCreator $creator)
	{
		parent::__construct();

		$this->creator = $creator;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$package = $this->buildPackage();

		$this->info('Creating workbench...');

		$path = $this->laravel['path.base'].'/workbench';

		// A "plain" package simply does not contain the "views", "config" or any other
		// Laravel intended directories. Plain packages don't contain those types of
		// directories as they are primarily just plain libraries for consumption.
		$plain = $this->input->getOption('plain');

		$workbench = $this->creator->create($package, $path, $plain);

		$this->info('Package workbench created!');

		// If the "composer" option has been specified, we will call composer update for
		// the workbench so the dependencies will be installed and the classmaps get
		// generated for the package. This will allow the devs to start migrating.
		if ($this->input->getOption('composer'))
		{
			$this->comment('Installing dependencies for workbench...');

			$this->callComposerUpdate($workbench);
		}
	}

	/**
	 * Call the composer update routine on the path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	protected function callComposerUpdate($path)
	{
		chdir($path);

		passthru('composer install --dev');
	}

	/**
	 * Build the package details from user input.
	 *
	 * @return Illuminate\Workbench\Package
	 */
	protected function buildPackage()
	{
		$vendor = studly_case($this->ask('What is vendor name of the package?'));

		$name = studly_case($this->ask('What is the package name?'));

		$author = $this->ask('What is your name?');

		$email = $this->ask('What is your e-mail address?');

		return new Package($vendor, $name, $author, $email);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('composer', null, InputOption::VALUE_NONE, 'Call "composer update" after workbench creation.'),

			array('plain', null, InputOption::VALUE_NONE, 'Skip creation of Laravel specific directories.'),
		);
	}

}
