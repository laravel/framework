<?php namespace Illuminate\Routing;

use ReflectionMethod;
use ReflectionFunctionAbstract;

trait RouteDependencyResolverTrait {

	/**
	 * Call a class method with the resolved dependencies.
	 *
	 * @param  object  $instance
	 * @param  string  $method
	 * @return mixed
	 */
	protected function callWithDependencies($instance, $method)
	{
		return call_user_func_array(
			[$instance, $method], $this->resolveClassMethodDependencies([], $instance, $method)
		);
	}

	/**
	 * Resolve the object method's type-hinted dependencies.
	 *
	 * @param  array  $parameters
	 * @param  object  $instance
	 * @param  string  $method
	 * @return array
	 */
	protected function resolveClassMethodDependencies(array $parameters, $instance, $method)
	{
		if ( ! method_exists($instance, $method)) return $parameters;

		return $this->resolveMethodDependencies(
			$parameters, new ReflectionMethod($instance, $method)
		);
	}

	/**
	 * Resolve the given method's type-hinted dependencies.
	 *
	 * @param  array  $parameters
	 * @param  \ReflectionFunctionAbstract  $reflector
	 * @return array
	 */
	public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
	{
		foreach ($reflector->getParameters() as $key => $parameter)
		{
			// If the parameter has a type-hinted class, we will check to see if it is already in
			// the list of parameters. If it is we will just skip it as it is probably a model
			// binding and we do not want to mess with those; otherwise, we resolve it here.
			$class = $parameter->getClass();

			if ($class && ! $this->alreadyInParameters($class->name, $parameters))
			{
				array_splice(
					$parameters, $key, 0, [$this->container->make($class->name)]
				);
			}
		}

		return $parameters;
	}

	/**
	 * Determine if an object of the given class is in a list of parameters.
	 *
	 * @param  string  $class
	 * @param  array  $parameters
	 * @return bool
	 */
	protected function alreadyInParameters($class, array $parameters)
	{
		return ! is_null(array_first($parameters, function($key, $value) use ($class)
		{
			return $value instanceof $class;
		}));
	}

}
