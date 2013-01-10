<?php namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Events\Dispatcher;
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
	 * The database seeder instance.
	 *
	 * @var Illuminate\Database\Seeder
	 */
	protected $seeder;

	/**
	 * The event dispatcher instance.
	 *
	 * @var Illuminate\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The path to the seed files.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Create a new database seed command instance.
	 *
	 * @param  Illuminate\Database\ConnectionResolverInterface  $resolver
	 * @param  Illuminate\Database\Seeder  $seeder
	 * @param  Illuminate\Events\Dispatcher  $events
	 * @param  string  $path
	 * @return void
	 */
	public function __construct(Resolver $resolver, Seeder $seeder, Dispatcher $events, $path)
	{
		parent::__construct();

		$this->path = $path;
		$this->seeder = $seeder;
		$this->events = $events;
		$this->resolver = $resolver;

		$this->registerSeedEventListener();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$name = $this->input->getOption('database');

		$total = $this->seeder->seed($this->resolver->connection($name), $this->path);

		if ($total == 0) $this->info('Nothing to seed.');
	}

	/**
	 * Register the seeding event listener.
	 *
	 * @return void
	 */
	protected function registerSeedEventListener()
	{
		$me = $this;

		$this->events->listen('illuminate.seeding', function($e) use ($me)
		{
			$message = "<info>Seeded table:</info> {$e->table} ({$e->count} records)";

			$me->getOutput()->writeln($message);
		});
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'),
		);
	}

}