<?php namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class SeedCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'db:seed';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Seed the database with records';

	/**
	 * The connection resolver instance.
	 *
	 * @var  Illuminate\Database\ConnectionResolverInterface
	 */
	protected $resolver;

	/**
	 * Create a new database seed command instance.
	 *
	 * @param  Illuminate\Database\ConnectionResolverInterface  $resolver
	 * @return void
	 */
	public function __construct(Resolver $resolver)
	{
		parent::__construct();

		$this->resolver = $resolver;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->resolver->setDefaultConnection($this->input->getOption('database'));

		$this->getSeeder()->run();

		$this->info('Database seeded!');
	}

	/**
	 * Get a seeder instance from the container.
	 *
	 * @return DatabaseSeeder
	 */
	protected function getSeeder()
	{
		return $this->laravel->make($this->input->getOption('class'));
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		$default = $this->laravel['config']['database.default'];

		return array(
			array('class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'DatabaseSeeder'),

			array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed', $default),
		);
	}

}