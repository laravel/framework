<?php namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Illuminate\Database\Seeder\SeedCreator;
use Symfony\Component\Console\Input\InputArgument;

class SeedMakeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:seed';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new seed file';

	/**
	 * The seeder creator instance.
	 *
	 * @var \Illuminate\Database\Seeder\SeederCreator
	 */
	protected $creator;

	/**
	 * @var \Illuminate\Foundation\Composer
	 */
	protected $composer;

	/**
	 * Create a new seeder install command instance.
	 *
	 * @param  \Illuminate\Database\Seeder\SeedCreator $creator
	 * @param  \Illuminate\Foundation\Composer $composer
	 * @return void
	 */
	public function __construct(SeedCreator $creator, Composer $composer)
	{
		parent::__construct();

		$this->creator = $creator;
		$this->composer = $composer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$name = $this->input->getArgument('name');

		// Now we are ready to write the seeder out to disk. Once we've written
		// the seeder out, we will dump-autoload for the entire framework to
		// make sure that the migrations are registered by the class loaders.
		$this->writeSeeder($name);

		$this->composer->dumpAutoloads();
	}

	/**
	 * Write the seeder file to disk.
	 *
	 * @param  string  $name
	 * @param  string  $table
	 * @param  bool    $create
	 * @return string
	 */
	protected function writeSeeder($name)
	{
		$path = $this->getSeedsPath();

		$file = pathinfo($this->creator->create($name, $path), PATHINFO_FILENAME);

		$this->line("<info>Created Seeder:</info> $file");
	}

	/**
	 * Get the path to the seeds directory.
	 *
	 * @return string
	 */
	protected function getSeedsPath()
	{
		return $this->laravel['path.database'] . '/seeds';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['name', InputArgument::REQUIRED, 'The name of the seeder'],
		];
	}

}
