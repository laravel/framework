<?php namespace Illuminate\Routing\Annotations\Annotations;

use ReflectionMethod;
use Illuminate\Routing\Annotations\Path;
use Illuminate\Routing\Annotations\MethodEndpoint;

abstract class Route extends Annotation {

	/**
	 * {@inheritdoc}
	 */
	public function modify(MethodEndpoint $endpoint, ReflectionMethod $method)
	{
		$endpoint->addPath(new Path(
			strtolower(class_basename(get_class($this))), $this->domain, $this->value,
			$this->as, (array) $this->middleware, (array) $this->where
		));
	}

}
