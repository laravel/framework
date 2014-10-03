<?php namespace Illuminate\Routing\Annotations\Annotations;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Annotations\MethodEndpoint;
use Illuminate\Routing\Annotations\EndpointCollection;

/**
 * @Annotation
 */
class After extends Filter {

	/**
	 * {@inheritdoc}
	 */
	public function modify(MethodEndpoint $endpoint, ReflectionMethod $method)
	{
		$this->applyToEndpoint($endpoint, 'before');
	}

	/**
	 * {@inheritdoc}
	 */
	public function modifyCollection(EndpointCollection $endpoints, ReflectionClass $class)
	{
		$this->apply($endpoints, 'after');
	}

}
