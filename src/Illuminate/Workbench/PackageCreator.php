<?php namespace Illuminate\Workbench;

use Illuminate\Filesystem\Filesystem;

class PackageCreator {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The basic building blocks of the package.
	 *
	 * @param  array
	 */
	protected $basicBlocks = array(
		'SupportFiles',
		'TestDirectory',
		'ServiceProvider'
	);

	/**
	 * The building blocks of the package.
	 *
	 * @param  array
	 */
	protected $blocks = array(
		'SupportFiles',
		'SupportDirectories',
		'PublicDirectory',
		'TestDirectory',
		'ServiceProvider'
	);

	/**
	 * Create a new package creator instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}

	/**
	 * Create a new package stub.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $path
	 * @param  bool    $plain
	 * @return string
	 */
	public function create(Package $package, $path, $plain = false)
	{
		$directory = $this->createDirectory($package, $path);

		$blocks = $plain ? $this->basicBlocks : $this->blocks;

		foreach ($blocks as $block)
		{
			$this->{"write{$block}"}($package, $directory, $plain);
		}

		return $directory;
	}

	/**
	 * Write the support files to the package root.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return void
	 */
	public function writeSupportFiles(Package $package, $directory, $plain)
	{
		foreach (array('PhpUnit', 'Travis', 'Composer') as $file)
		{
			$this->{"write{$file}File"}($package, $directory, $plain);
		}
	}

	/**
	 * Write the PHPUnit stub file.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return void
	 */
	protected function writePhpUnitFile(Package $package, $directory)
	{
		$this->files->copy(__DIR__.'/stubs/phpunit.xml', $directory.'/phpunit.xml');
	}

	/**
	 * Write the Travis stub file.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return void
	 */
	protected function writeTravisFile(Package $package, $directory)
	{
		$this->files->copy(__DIR__.'/stubs/.travis.yml', $directory.'/.travis.yml');
	}

	/**
	 * Write the Composer.json stub file.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return void
	 */
	protected function writeComposerFile(Package $package, $directory, $plain)
	{
		$stub = $this->getComposerStub($plain);

		$stub = $this->formatPackageStub($stub, $package);

		$this->files->put($directory.'/composer.json', $stub);
	}

	/**
	 * Get the Composer.json stub file contents.
	 *
	 * @param  bool    $plain
	 * @return string
	 */
	protected function getComposerStub($plain)
	{
		if ($plain)
		{
			return $this->files->get(__DIR__.'/stubs/plain.composer.json');
		}
		else
		{
			return $this->files->get(__DIR__.'/stubs/composer.json');
		}
	}

	/**
	 * Create the support directories for a package.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return void
	 */
	public function writeSupportDirectories(Package $package, $directory)
	{
		foreach (array('config', 'lang', 'migrations', 'views') as $support)
		{
			// Once we create the source directory, we will write an empty file to the
			// directory so that it will be kept in source control allowing the dev
			// to go ahead and push these components to Github right on creation.
			$path = $directory.'/src/'.$support;

			$this->files->makeDirectory($path, 0777, true);

			$this->files->put($path.'/.gitkeep', '');
		}
	}

	/**
	 * Create the public directory for the package.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return void
	 */
	public function writePublicDirectory(Package $package, $directory, $plain)
	{
		if ($plain) return;

		$this->files->makeDirectory($directory.'/public');

		$this->files->put($directory.'/public/.gitkeep', '');
	}

	/**
	 * Create the test directory for the package.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return void
	 */
	public function writeTestDirectory(Package $package, $directory)
	{
		$this->files->makeDirectory($directory.'/tests');

		$this->files->put($directory.'/tests/.gitkeep', '');
	}

	/**
	 * Write the stub ServiceProvider for the package.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return void
	 */
	public function writeServiceProvider(Package $package, $directory, $plain)
	{
		// Once we have the service provider stub, we will need to format it and make
		// the necessary replacements to the class, namespaces, etc. Then we'll be
		// able to write it out into the package's workbench directory for them.
		$stub = $this->getProviderStub($plain);

		$stub = $this->formatPackageStub($stub, $package);

		$path = $this->createClassDirectory($package, $directory);

		// The primary source directory where the package's classes will live may not
		// exist yet, so we will need to create it before we write these providers
		// out to that location. We'll go ahead and create now here before then.
		$file = $path.'/'.$package->name.'ServiceProvider.php';

		$this->files->put($file, $stub);
	}

	/**
	 * Get the stub for a ServiceProvider.
	 *
	 * @param  bool    $plain
	 * @return string
	 */
	protected function getProviderStub($plain)
	{
		if ($plain)
		{
			return $this->files->get(__DIR__.'/stubs/plain.provider.php');
		}
		else
		{
			return $this->files->get(__DIR__.'/stubs/provider.php');
		}
	}

	/**
	 * Create the main source directory for the package.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $directory
	 * @return string
	 */
	protected function createClassDirectory(Package $package, $directory)
	{
		$path = $directory.'/src/'.$package->vendor.'/'.$package->name;

		if ( ! $this->files->isDirectory($path))
		{
			$this->files->makeDirectory($path, 0777, true);
		}

		return $path;
	}

	/**
	 * Format a generic package stub file.
	 *
	 * @param  string  $stub
	 * @param  Illuminate\Workbench\Package  $package
	 * @return string
	 */
	protected function formatPackageStub($stub, Package $package)
	{
		// When replacing values in the stub, we can just take the object vars of
		// the package and snake case them. This should give us the array with
		// all the necessary replacements variables present and ready to go.
		foreach (get_object_vars($package) as $key => $value)
		{
			$key = '{{'.snake_case($key).'}}';

			$stub = str_replace($key, $value, $stub);
		}
		
		return $stub;
	}

	/**
	 * Create a workbench directory for the package.
	 *
	 * @param  Illuminate\Workbench\Package  $package
	 * @param  string  $path
	 * @return string
	 */
	protected function createDirectory(Package $package, $path)
	{
		$fullPath = $path.'/'.$package->getFullName();

		// If the directory doesn't exist, we will go ahead and create the package
		// directory in the workbench location. We will use this entire package
		// name when creating the directory to avoid any potential conflicts.
		if ( ! $this->files->isDirectory($fullPath))
		{
			$this->files->makeDirectory($fullPath, 0777, true);

			return $fullPath;
		}

		throw new \InvalidArgumentException("Package exists.");
	}

}