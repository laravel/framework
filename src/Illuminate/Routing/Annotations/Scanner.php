<?php namespace Illuminate\Routing\Annotations;

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
	 * The root namespace of the controllers.
	 *
	 * @var string
	 */
	protected $rootNamespace;

	/**
	 * Create a new scanner instance.
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
	public function getRouteDefinitions()
	{
		$output = '';

		foreach ($this->getEndpointsInClasses($this->getReader()) as $endpoint)
			$output .= $endpoint->toRouteDefinition().PHP_EOL;

		return trim($output);
	}

	/**
	 * Scan the directory and generate the route manifest.
	 *
	 * @param  SimpleAnnotationReader  $reader
	 * @return EndpointCollection
	 */
	protected function getEndpointsInClasses(SimpleAnnotationReader $reader)
	{
		$endpoints = new EndpointCollection;

		foreach ($this->getClassesInScanPath() as $class)
		{
			$endpoints = $endpoints->merge($this->getEndpointsInClass(
				$class, new AnnotationSet($class, $reader)
			));
		}

		return $endpoints;
	}

	/**
	 * Build the Endpoints for the given class.
	 *
	 * @param  string  $class
	 * @param  AnnotationSet  $annotations
	 * @return EndpointCollection
	 */
	protected function getEndpointsInClass(ReflectionClass $class, AnnotationSet $annotations)
	{
		$endpoints = new EndpointCollection;

		foreach ($annotations->method as $method => $methodAnnotations)
			$this->addEndpoint($endpoints, $class, $method, $methodAnnotations);

		foreach ($annotations->class as $annotation)
			$annotation->modifyCollection($endpoints, $class);

		return $endpoints;
	}

	/**
	 * Create a new endpoint in the collection.
	 *
	 * @param  EndpointCollection  $endpoints
	 * @param  ReflectionClass  $class
	 * @param  string  $method
	 * @param  array  $annotations
	 * @return void
	 */
	protected function addEndpoint(EndpointCollection $endpoints, ReflectionClass $class,
                                   $method, array $annotations)
	{
		$endpoints->push($endpoint = new MethodEndpoint([
			'reflection' => $class, 'method' => $method, 'uses' => $class->name.'@'.$method
		]));

		foreach ($annotations as $annotation)
			$annotation->modify($endpoint, $class->getMethod($method));
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
				->addNamespace('Illuminate\Routing\Annotations\Annotations');

		return $reader;
	}

}
