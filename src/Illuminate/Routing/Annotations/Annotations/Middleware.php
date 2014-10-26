<?php namespace Illuminate\Routing\Annotations\Annotations;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Annotations\MethodEndpoint;
use Illuminate\Routing\Annotations\EndpointCollection;

/**
 * @Annotation
 */
class Middleware extends Annotation {

	/**
	 * {@inheritdoc}
	 */
	public function modify(MethodEndpoint $endpoint, ReflectionMethod $method)
	{
		if ($endpoint->hasPaths())
		{
			foreach ($endpoint->getPaths() as $path)
			{
				$path->middleware = array_merge($path->middleware, (array) $this->value);
			}
		}
		else
		{
			$endpoint->middleware = array_merge($endpoint->middleware, (array) $this->value);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function modifyCollection(EndpointCollection $endpoints, ReflectionClass $class)
	{
		foreach ($endpoints as $endpoint)
		{
			foreach ((array) $this->value as $middleware)
			{
				$endpoint->classMiddleware[] = [
					'name' => $middleware, 'only' => (array) $this->only, 'except' => (array) $this->except
				];
			}
		}
	}

}
