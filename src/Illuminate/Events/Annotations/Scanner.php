<?php namespace Illuminate\Events\Annotations;

use SplFileInfo;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

class Scanner {

	/**
	 * The path to scan for annotations.
	 *
	 * @var string
	 */
	protected $scan;

	/**
	 * The application's root namespace.
	 *
	 * @var string
	 */
	protected $rootNamespace;

	/**
	 * Create a new event scanner instance.
	 *
	 * @param  string  $scan
	 * @param  string  $rootNamespace
	 * @return void
	 */
	public function __construct($scan, $rootNamespace)
	{
		$this->scan = $scan;
		$this->rootNamespace = rtrim($rootNamespace, '\\').'\\';

		foreach (Finder::create()->files()->in(__DIR__.'/Annotations') as $file)
			AnnotationRegistry::registerFile($file->getRealPath());
	}

	/**
	 * Create a new scanner instance.
	 *
	 * @param  string  $scan
	 * @param  string  $rootNamespace
	 * @return static
	 */
	 public static function create($scan, $rootNamespace)
	 {
	 	return new static($scan, $rootNamespace);
	 }

	/**
	 * Convert the scanned annotations into route definitions.
	 *
	 * @return string
	 */
	public function getEventDefinitions()
	{
		$output = '';

		$reader = $this->getReader();

		foreach ($this->getClassesInScanPath() as $class)
		{
			foreach ($class->getMethods() as $method)
			{
				foreach ($reader->getMethodAnnotations($method) as $annotation)
				{
					$output .= $this->buildListener($class->name, $method->name, $annotation->events);
				}
			}
		}

		return trim($output);
	}

	/**
	 * Build the event listener for the class and method.
	 *
	 * @param  string  $class
	 * @param  string  $method
	 * @param  array  $events
	 * @return string
	 */
	protected function buildListener($class, $method, $events)
	{
		return sprintf('$events->listen(%s, \''.$class.'@'.$method.'\');', var_export($events, true)).PHP_EOL;
	}

	/**
	 * Get all of the ReflectionClass instances in the scan path.
	 *
	 * @return array
	 */
	protected function getClassesInScanPath()
	{
		$classes = [];

		foreach ($this->getFilesInScanPath() as $file)
		{
			try {
				$classes[] = new ReflectionClass($this->getClassName($file));
			} catch (\Exception $e) {
				//
			}
		}

		return $classes;
	}

	/**
	 * Get the files in the scan path.
	 *
	 * @return Finder
	 */
	protected function getFilesInScanPath()
	{
		return Finder::create()->files()->in($this->scan)->notName('routes.php');
	}

	/**
	 * Get the class name from the given file.
	 *
	 * @param  \SplFileInfo  $file
	 * @return string
	 */
	public function getClassName(SplFileInfo $file)
	{
		return $this->rootNamespace.str_replace(
			'.php', '', str_replace('/', '\\', $this->getFilePathWithoutScanPath($file))
		);
	}

	/**
	 * Get the file path with the scan path removed.
	 *
	 * @param  \SplFileInfo  $file
	 * @return string
	 */
	protected function getFilePathWithoutScanPath(SplFileInfo $file)
	{
		return trim(str_replace($this->scan, '', $file->getRealPath()), DIRECTORY_SEPARATOR);
	}

	/**
	 * Get an annotation reader instance.
	 *
	 * @return \Doctrine\Common\Annotations\SimpleAnnotationReader
	 */
	protected function getReader()
	{
		with($reader = new SimpleAnnotationReader)
				->addNamespace('Illuminate\Events\Annotations\Annotations');

		return $reader;
	}

}
