<?php namespace Illuminate\Database;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;

class Seeder {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The event dispatcher instance.
	 *
	 * @var Illuminate\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The database seed file list.
	 *
	 * @var array
	 */
	protected $seeds;

	/**
	 * Create a new database seeder instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @param  Illuminate\Events\Dispatcher  $events
	 * @return void
	 */
	public function __construct(Filesystem $files, Dispatcher $events = null)
	{
		$this->files = $files;
		$this->events = $events;
	}

	/**
	 * Seed the given connection from the given path.
	 *
	 * @param  Illuminate\Database\Connection  $connection
	 * @param  string  $path
	 * @return int
	 */
	public function seed(Connection $connection, $path)
	{
		$total = 0;

		foreach ($this->getFiles($path) as $file)
		{
			$records = $this->files->getRequire($file);

			// We'll grab the table name here, which could either come from the array or
			// from the filename itself. Then, we will simply insert the records into
			// the databases via a connection and fire an event noting the seeding.
			$table = $this->getTable($records, $file);

			$connection->table($table)->truncate();

			$connection->table($table)->insert($records);

			$total += $count = count($records);

			// Once we have seeded the table, we will fire an event to let any listeners
			// know the tables have been seeded and how many records were inserted so
			// information can be presented to the developer about the seeding run.
			if (isset($this->events))
			{
				$payload = compact('table', 'count');

				$this->events->fire('illuminate.seeding', $payload);
			}
		}

		return $total;
	}

	/**
	 * Get all of the files at a given path.
	 *
	 * @param  string  $path
	 * @return array
	 */
	protected function getFiles($path)
	{
		if (isset($this->seeds)) return $this->seeds;

		// If the seeds haven't been read before, we will glob the directory and sort
		// them alphabetically just in case the developer is using numbers to make
		// the seed run in a certain order based on their database design needs.
		$files = $this->files->glob($path.'/*.php');

		sort($files);

		return $this->seeds = $files;
	}

	/**
	 * Get the table from the given records and file.
	 *
	 * @param  array   $records
	 * @param  string  $file
	 * @return string
	 */
	protected function getTable( & $records, $file)
	{
		$table = array_get($records, 'table', basename($file, '.php'));

		unset($records['table']);

		return $table;
	}

}