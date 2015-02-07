<?php namespace Illuminate\Database\Seeder;

use Illuminate\Filesystem\Filesystem;

class SeedCreator {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new seed creator instance
	 *
	 * @param \Illuminate\Filesystem\Filesystem $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}

	/**
	 * Create a new seeder at the given path.
	 *
	 * @param  string $name
	 * @param  string $path
	 * @return string
	 */
	public function create($name, $path)
	{
		$path = $this->getPath($name, $path);

		// First we will ge the stub file for seed. Once we have those we will
		// populate the various place-holders, save the file, and run the post
		// create event
		$stub = $this->getStub();

		$this->files->put($path, $this->populateStub($name, $stub));

		return $path;
	}

	/**
	 * Get the seeder stub file.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return $this->files->get(__DIR__ . '/blank.stub');
	}

	/**
	 * Populate the place-holders in the seeder stub.
	 *
	 * @param  string $name
	 * @param  string $stub
	 * @return string
	 */
	protected function populateStub($name, $stub)
	{
		$stub = str_replace('{{class}}', studly_case($name) . 'TableSeeder', $stub);

		return $stub;
	}

	/**
	 * Get the full path to the seeder.
	 *
	 * @param  string $name
	 * @param  string $path
	 * @return string
	 */
	public function getPath($name, $path)
	{
		return $path . '/' . $name . 'TableSeeder.php';
	}

}
