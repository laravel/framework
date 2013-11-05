<?php namespace Illuminate\Database\Migrations;

use Illuminate\Filesystem\Filesystem;

class MigrationPublisher {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * The path in which packages are located.
	 *
	 * @var string
	 */
	protected $packagePath;

	/**
	 * The path migrations should be published to.
	 *
	 * @var string
	 */
	protected $migrationsPath;

	/**
	 * Create a new publisher instance.
	 *
	 * @param \Illuminate\Filesystem\Filesystem  $files
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}

	/**
	 * Search the source path for migration files.
	 *
	 * @param string $path
	 * 
	 * @return void
	 */
	public function setSourcePath($path)
	{
		$this->source = $this->files->glob($path . '/*.php');
	}

	/**
	 * Set the destination path.
	 *
	 * @param string $path
	 */
	public function setDestinationPath($path)
	{
		$this->destination = $path;
	}

	/**
	 * Check whether the source has any migrations.
	 *
	 * @return boolean
	 */
	public function sourceHasMigrations()
	{
		return $this->files !== false;
	}

	/**
	 * Get the source files.
	 *
	 * @return array|false
	 */
	public function getSourceFiles()
	{
		return $this->source;
	}

	/**
	 * Copy a migration.
	 *
	 * @param  string $source
	 *
	 * @return boolean
	 */
	public function publish($source)
	{
		$name = $this->getWithUpdatedTimestamp($source);
		return $this->files->copy($source, $this->destination . '/' . $name);
	}

	/**
	 * Check if the name of a migration is valid. To be considered valid, the
	 * name must follow the format of a timestamp formatted as 'Y_m_d_His',
	 * followed by only lowercase characters and underscores. The filename must
	 * end in .php.
	 *
	 * @param  string $filename
	 *
	 * @return boolean
	 */
	public function validMigrationName($filename)
	{
		$filename = basename($filename);
		return preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}[a-z_]+\.php$/', $filename) === 1;
	}

	/**
	 * Check if a migration already exists in the user's migration directory.
	 *
	 * @param  string $filename
	 *
	 * @return boolean
	 */
	public function migrationExists($filename)
	{
		$migrationName = $this->getMigrationName($filename);

		$files = $this->files->glob($this->destination . '/*.php');

		if ($files === false)
		{
			return true;
		}

		foreach ($files as $file)
		{
			if ($migrationName == $this->getMigrationName($file))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Update the timestamp in a migration's filename.
	 *
	 * @param  string $filename
	 *
	 * @return string
	 */
	protected function getWithUpdatedTimestamp($filename)
	{
		$filename = basename($filename);
		return date('Y_m_d_His') . $this->getMigrationName($filename);
	}

	/**
	 * Get the name of a migration, without its timestamp, assuming it is a
	 * well-formatted migration name.
	 *
	 * @param  string $filename
	 *
	 * @return string
	 */
	protected function getMigrationName($filename)
	{
		$filename = basename($filename);
		return substr($filename, 17);
	}
}
