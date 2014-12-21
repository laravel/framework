<?php namespace Illuminate\Routing\Annotations;

use ReflectionClass;
use Doctrine\Common\Annotations\SimpleAnnotationReader;

class AnnotationSet {

	/**
	 * The class annotations.
	 *
	 * @var array
	 */
	public $class;

	/**
	 * The method annotations.
	 *
	 * @var array
	 */
	public $method;

	/**
	 * Create a new annotation set instance.
	 *
	 * @param  \ReflectionClass  $class
	 * @param  \Doctrine\Common\Annotations\SimpleAnnotationReader  $reader
	 * @return void
	 */
	public function __construct(ReflectionClass $class, SimpleAnnotationReader $reader)
	{
		$this->class = $reader->getClassAnnotations($class);
		$this->method = $this->getMethodAnnotations($class, $reader);
	}

	/**
	 * Get the method annotations for a given class.
	 *
	 * @param  \ReflectionClass  $class
	 * @param  \Doctrine\Common\Annotations\SimpleAnnotationReader  $reader
	 * @return array
	 */
	protected function getMethodAnnotations(ReflectionClass $class, SimpleAnnotationReader $reader)
	{
		$annotations = [];

		foreach ($class->getMethods() as $method)
		{
			$results = $reader->getMethodAnnotations($method);

			if (count($results) > 0)
				$annotations[$method->name] = $results;
		}

		return $annotations;
	}

}
