<?php namespace Illuminate\Events\Annotations;

use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

class Scanner {

	/**
	 * The classes to scan for annotations.
	 *
	 * @var array
	 */
	protected $scan;

	/**
	 * Create a new event scanner instance.
	 *
	 * @param  array  $scan
	 * @return void
	 */
	public function __construct(array $scan)
	{
		$this->scan = $scan;

		foreach (Finder::create()->files()->in(__DIR__.'/Annotations') as $file)
		{
			AnnotationRegistry::registerFile($file->getRealPath());
		}
	}

	/**
	 * Create a new scanner instance.
	 *
	 * @param  array  $scan
	 * @return static
	 */
	 public static function create(array $scan)
	 {
	 	return new static($scan);
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

		foreach ($this->getClassesToScan() as $class)
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
	protected function getClassesToScan()
	{
		$classes = [];

		foreach ($this->scan as $class)
		{
			try
			{
				$classes[] = new ReflectionClass($class);
			}
			catch (\Exception $e)
			{
				//
			}
		}

		return $classes;
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
